<?php

namespace Tests\Unit;

use App\Services\Discovery\HostDiscoveryService;
use App\Services\Discovery\PortScanService;
use App\Services\Discovery\Probes\HostProbe;
use App\Services\Discovery\Probes\HostProbeResult;
use App\Services\Discovery\Probes\PortProbeResult;
use App\Services\Discovery\Probes\TcpPortProbe;
use PHPUnit\Framework\TestCase;

final class DiscoveryRetryServicesTest extends TestCase
{
    public function test_ping_retries_only_unreachable_hosts(): void
    {
        $probe = new class implements HostProbe
        {
            /** @var list<list<string>> */
            public array $batches = [];

            public function pingBatch(array $ips, int $timeoutSeconds): array
            {
                $this->batches[] = $ips;
                $attempt = count($this->batches);

                return collect($ips)->mapWithKeys(function (string $ip) use ($attempt): array {
                    $reachable = $ip === '10.0.0.2' || $attempt === 2;

                    return [$ip => new HostProbeResult($reachable, $reachable ? 7 : null)];
                })->all();
            }

            public function reverseDns(string $ip): ?string
            {
                return null;
            }
        };

        $results = (new HostDiscoveryService($probe))->pingBatch(['10.0.0.1', '10.0.0.2'], 1, 1);

        self::assertSame([['10.0.0.1', '10.0.0.2'], ['10.0.0.1']], $probe->batches);
        self::assertTrue($results['10.0.0.1']['reachable']);
        self::assertTrue($results['10.0.0.2']['reachable']);
    }

    public function test_tcp_scan_retries_only_closed_ports(): void
    {
        $probe = new class implements TcpPortProbe
        {
            /** @var list<array<string, list<int>>> */
            public array $targets = [];

            public function scanMany(array $targets, int $timeoutSeconds): array
            {
                $this->targets[] = $targets;
                $attempt = count($this->targets);
                $results = [];

                foreach ($targets as $ip => $ports) {
                    foreach ($ports as $port) {
                        $open = $port === 443 || $attempt === 2;
                        $results[$ip][$port] = new PortProbeResult($open, $open ? 4 : null);
                    }
                }

                return $results;
            }
        };

        $results = (new PortScanService($probe))->scanMany(['10.0.0.1' => [443, 11112]], 1, 1);

        self::assertSame(['10.0.0.1' => [11112]], $probe->targets[1]);
        self::assertTrue($results['10.0.0.1'][443]['open']);
        self::assertTrue($results['10.0.0.1'][11112]['open']);
    }

    public function test_retry_count_is_hard_limited_to_three_repetitions(): void
    {
        $probe = new class implements HostProbe
        {
            public int $calls = 0;

            public function pingBatch(array $ips, int $timeoutSeconds): array
            {
                $this->calls++;

                return ['10.0.0.1' => new HostProbeResult(false)];
            }

            public function reverseDns(string $ip): ?string
            {
                return null;
            }
        };

        (new HostDiscoveryService($probe))->pingBatch(['10.0.0.1'], 1, 99);

        self::assertSame(4, $probe->calls);
    }
}
