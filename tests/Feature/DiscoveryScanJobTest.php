<?php

namespace Tests\Feature;

use App\Jobs\RunDiscoveryScanJob;
use App\Models\DiscoveredHost;
use App\Models\DiscoveryRun;
use App\Services\Discovery\DiscoveryEchoCommandResult;
use App\Services\Discovery\DiscoveryEchoCommandRunner;
use App\Services\Discovery\Probes\HostProbe;
use App\Services\Discovery\Probes\HostProbeResult;
use App\Services\Discovery\Probes\PortProbeResult;
use App\Services\Discovery\Probes\TcpPortProbe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Führt einen kompletten Scan-Job gegen ausschließlich gemockte Probes/Runner
 * aus - es wird zu keinem Zeitpunkt ein echter Ping, TCP-Connect oder
 * DCMTK-Prozess gestartet (siehe Abschnitt 25 des Lastenhefts).
 */
final class DiscoveryScanJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_full_scan_produces_a_confirmed_dicom_candidate_and_completes(): void
    {
        $this->seed();

        $this->app->bind(HostProbe::class, fn () => new class implements HostProbe
        {
            public function pingBatch(array $ips, int $timeoutSeconds): array
            {
                $results = [];
                foreach ($ips as $ip) {
                    $results[$ip] = new HostProbeResult(true, 4);
                }

                return $results;
            }

            public function reverseDns(string $ip): ?string
            {
                return 'pacs-archive-01';
            }
        });

        $this->app->bind(TcpPortProbe::class, fn () => new class implements TcpPortProbe
        {
            public function scanMany(array $targets, int $timeoutSeconds): array
            {
                $results = [];
                foreach ($targets as $ip => $ports) {
                    foreach ($ports as $port) {
                        $results[$ip][$port] = new PortProbeResult($port === 11112, $port === 11112 ? 6 : null);
                    }
                }

                return $results;
            }
        });

        $this->app->bind(DiscoveryEchoCommandRunner::class, fn () => new class implements DiscoveryEchoCommandRunner
        {
            public function runMany(array $targets, int $timeoutSeconds): array
            {
                $results = [];
                foreach ($targets as $target) {
                    $results[$target->key()] = new DiscoveryEchoCommandResult($target, true, true, null, null, 'C-ECHO ok', 9);
                }

                return $results;
            }
        });

        $run = DiscoveryRun::factory()->create([
            'ip_range' => '192.168.20.10',
            'exclude_ips' => [],
            'total_ips' => 1,
            'status' => DiscoveryRun::STATUS_PENDING,
        ]);
        $run->ports()->create(['port' => 11112, 'protocol' => 'tcp', 'label' => 'DICOM', 'is_dicom_candidate' => true, 'enabled' => true]);
        $run->ports()->create(['port' => 443, 'protocol' => 'tcp', 'label' => 'HTTPS', 'is_dicom_candidate' => false, 'enabled' => true]);
        $run->aeCandidates()->create(['ae_title' => 'HNR_DISCOVERY', 'role' => 'calling', 'source' => 'default']);
        $run->aeCandidates()->create(['ae_title' => 'PACS01', 'role' => 'called', 'source' => 'manual']);

        $job = new RunDiscoveryScanJob($run->id);
        $this->app->call([$job, 'handle']);

        $run->refresh();
        self::assertSame(DiscoveryRun::STATUS_COMPLETED, $run->status);
        self::assertSame(1, $run->processed_ips);
        self::assertSame(100, $run->progress_percentage);
        self::assertSame(1, $run->found_hosts_count);
        self::assertSame(1, $run->dicom_candidates_count);

        $host = DiscoveredHost::query()->where('discovery_run_id', $run->id)->firstOrFail();
        self::assertTrue($host->is_reachable);
        self::assertSame('pacs-archive-01', $host->hostname);
        self::assertSame('pacs', $host->suggested_system_type);
        self::assertGreaterThanOrEqual(55, $host->confidence_percentage);

        $this->assertDatabaseHas('discovered_ports', ['discovered_host_id' => $host->id, 'port' => 11112, 'is_open' => true]);
        $this->assertDatabaseHas('discovered_ports', ['discovered_host_id' => $host->id, 'port' => 443, 'is_open' => false]);
        $this->assertDatabaseHas('dicom_discovery_results', ['discovered_host_id' => $host->id, 'called_ae' => 'PACS01', 'echo_successful' => true]);
        $this->assertDatabaseHas('discovery_classification_evidence', ['discovered_host_id' => $host->id, 'rule_name' => 'dicom_echo_successful']);
        $this->assertDatabaseHas('security_events', ['event_type' => 'discovery.run.completed']);
    }

    public function test_a_run_cancelled_mid_flight_stops_before_further_batches(): void
    {
        $this->seed();

        $run = DiscoveryRun::factory()->create([
            'ip_range' => '192.168.20.8/29', // 8 Adressen
            'exclude_ips' => [],
            'total_ips' => 8,
            'status' => DiscoveryRun::STATUS_PENDING,
            'scan_options' => ['ping_enabled' => true, 'reverse_dns_enabled' => false, 'tcp_scan_enabled' => false, 'dicom_check_enabled' => false, 'scan_unresponsive_hosts' => false, 'max_parallel_hosts' => 4, 'timeout_seconds' => 1, 'retries' => 0, 'profile' => 'custom'],
        ]);
        $run->ports()->create(['port' => 11112, 'protocol' => 'tcp', 'label' => 'DICOM', 'is_dicom_candidate' => true, 'enabled' => true]);

        // Simuliert einen Benutzer, der den Lauf während der Verarbeitung des
        // ersten Batches abbricht (siehe DiscoveryRunService::cancel()).
        $this->app->bind(HostProbe::class, fn () => new class($run->id) implements HostProbe
        {
            public function __construct(private readonly int $runId) {}

            public function pingBatch(array $ips, int $timeoutSeconds): array
            {
                DiscoveryRun::query()->whereKey($this->runId)->update(['status' => DiscoveryRun::STATUS_CANCELLING]);

                $results = [];
                foreach ($ips as $ip) {
                    $results[$ip] = new HostProbeResult(false);
                }

                return $results;
            }

            public function reverseDns(string $ip): ?string
            {
                return null;
            }
        });

        $job = new RunDiscoveryScanJob($run->id);
        $this->app->call([$job, 'handle']);

        $run->refresh();
        self::assertSame(DiscoveryRun::STATUS_CANCELLED, $run->status);
        self::assertSame(4, $run->processed_ips); // nur der erste Batch (max_parallel_hosts=4) wurde verarbeitet
        self::assertSame(0, $run->found_hosts_count);
    }
}
