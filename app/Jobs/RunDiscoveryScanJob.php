<?php

namespace App\Jobs;

use App\Models\DicomNode;
use App\Models\DiscoveredHost;
use App\Models\DiscoveryAeCandidate;
use App\Models\DiscoveryRun;
use App\Models\System;
use App\Services\Discovery\Classification\ClassificationContext;
use App\Services\Discovery\Classification\ClassificationService;
use App\Services\Discovery\DicomEchoScanService;
use App\Services\Discovery\DiscoveryAuditService;
use App\Services\Discovery\DiscoveryEchoTarget;
use App\Services\Discovery\HostDiscoveryService;
use App\Services\Discovery\NetworkRangeService;
use App\Services\Discovery\PortScanService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Führt einen kompletten Discovery-Lauf aus: Phase A (Host-Erkennung),
 * Phase B (Portprüfung), Phase C (DICOM-C-ECHO), Klassifizierung.
 *
 * Verarbeitet IP-Adressen in Batches (Größe = scan_options.max_parallel_hosts,
 * hart begrenzt durch config('discovery.max_parallel_hosts')). Innerhalb
 * eines Batches laufen Ping, Portscan und C-ECHO jeweils parallel
 * (nicht-blockierende Prozesse/Sockets, siehe Probes-Klassen). Ein
 * Fehler bei einem einzelnen Host bricht den restlichen Lauf nicht ab.
 */
final class RunDiscoveryScanJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 1;

    public int $timeout;

    public function __construct(public readonly int $discoveryRunId)
    {
        $this->onQueue('discovery');
        $this->timeout = (int) config('discovery.job_timeout_seconds', 3600);
    }

    public function handle(
        NetworkRangeService $rangeService,
        HostDiscoveryService $hostDiscoveryService,
        PortScanService $portScanService,
        DicomEchoScanService $echoScanService,
        ClassificationService $classificationService,
        DiscoveryAuditService $auditService,
    ): void {
        $run = DiscoveryRun::query()->find($this->discoveryRunId);

        if (! $run instanceof DiscoveryRun || ! in_array($run->status, [DiscoveryRun::STATUS_PENDING, DiscoveryRun::STATUS_RUNNING], true)) {
            return;
        }

        $run->update(['status' => DiscoveryRun::STATUS_RUNNING, 'started_at' => $run->started_at ?? now()]);

        $hadErrors = false;

        try {
            $options = (array) $run->scan_options;
            $pingEnabled = (bool) ($options['ping_enabled'] ?? true);
            $reverseDnsEnabled = (bool) ($options['reverse_dns_enabled'] ?? true);
            $tcpScanEnabled = (bool) ($options['tcp_scan_enabled'] ?? true);
            $dicomCheckEnabled = (bool) ($options['dicom_check_enabled'] ?? true);
            $scanUnresponsiveHosts = (bool) ($options['scan_unresponsive_hosts'] ?? false);
            $timeoutSeconds = max(1, (int) ($options['timeout_seconds'] ?? 2));
            $maxParallelHosts = min(
                (int) config('discovery.max_parallel_hosts', 16),
                max(1, (int) ($options['max_parallel_hosts'] ?? 8)),
            );

            $ports = $run->ports()->where('enabled', true)->get(['port', 'is_dicom_candidate']);
            $portNumbers = $ports->pluck('port')->map(fn ($port): int => (int) $port)->all();
            $dicomCandidatePorts = $ports->where('is_dicom_candidate', true)->pluck('port')->map(fn ($port): int => (int) $port)->values()->all();

            $callingAeTitles = $run->aeCandidates()->where('role', DiscoveryAeCandidate::ROLE_CALLING)->pluck('ae_title')->all();
            $calledAeTitles = $run->aeCandidates()->where('role', DiscoveryAeCandidate::ROLE_CALLED)->pluck('ae_title')->all();
            if ($callingAeTitles === []) {
                $callingAeTitles = [config('discovery.default_calling_ae_title', 'HNR_DISCOVERY')];
            }

            $addresses = $rangeService->expand($run->ip_range, (array) $run->exclude_ips);
            $webPorts = config('discovery.web_ports', [80, 443]);
            $databasePorts = config('discovery.database_ports', []);

            foreach (array_chunk($addresses, max(1, $maxParallelHosts)) as $batch) {
                $run->refresh();
                if ($run->status === DiscoveryRun::STATUS_CANCELLING) {
                    break;
                }

                try {
                    $this->processBatch(
                        batch: $batch,
                        run: $run,
                        pingEnabled: $pingEnabled,
                        reverseDnsEnabled: $reverseDnsEnabled,
                        tcpScanEnabled: $tcpScanEnabled,
                        dicomCheckEnabled: $dicomCheckEnabled,
                        scanUnresponsiveHosts: $scanUnresponsiveHosts,
                        timeoutSeconds: $timeoutSeconds,
                        portNumbers: $portNumbers,
                        dicomCandidatePorts: $dicomCandidatePorts,
                        callingAeTitles: $callingAeTitles,
                        calledAeTitles: $calledAeTitles,
                        webPorts: $webPorts,
                        databasePorts: $databasePorts,
                        hostDiscoveryService: $hostDiscoveryService,
                        portScanService: $portScanService,
                        echoScanService: $echoScanService,
                        classificationService: $classificationService,
                    );
                } catch (Throwable $exception) {
                    $hadErrors = true;
                    Log::warning('Discovery-Batch fehlgeschlagen', [
                        'discovery_run_id' => $run->id,
                        'batch' => $batch,
                        'error' => $exception->getMessage(),
                    ]);
                }

                $run->increment('processed_ips', count($batch));
                $run->refresh();
                $run->update([
                    'progress_percentage' => $run->total_ips > 0 ? min(100, (int) round($run->processed_ips / $run->total_ips * 100)) : 100,
                    'found_hosts_count' => $run->hosts()->count(),
                    'dicom_candidates_count' => $run->hosts()->whereHas('ports', fn ($q) => $q->where('is_dicom_candidate', true)->where('is_open', true))->count(),
                ]);
            }

            $run->refresh();
            $finalStatus = match (true) {
                $run->status === DiscoveryRun::STATUS_CANCELLING => DiscoveryRun::STATUS_CANCELLED,
                $hadErrors => DiscoveryRun::STATUS_PARTIALLY_FAILED,
                default => DiscoveryRun::STATUS_COMPLETED,
            };

            $run->update(['status' => $finalStatus, 'finished_at' => now(), 'progress_percentage' => 100]);
            $auditService->runCompleted($run);
        } catch (Throwable $exception) {
            $run->update([
                'status' => DiscoveryRun::STATUS_FAILED,
                'finished_at' => now(),
                'error_message' => mb_substr($exception->getMessage(), 0, 2000),
            ]);
            $auditService->runCompleted($run);

            throw $exception;
        }
    }

    /**
     * @param  list<string>  $batch
     * @param  list<int>  $portNumbers
     * @param  list<int>  $dicomCandidatePorts
     * @param  list<string>  $callingAeTitles
     * @param  list<string>  $calledAeTitles
     * @param  list<int>  $webPorts
     * @param  list<int>  $databasePorts
     */
    private function processBatch(
        array $batch,
        DiscoveryRun $run,
        bool $pingEnabled,
        bool $reverseDnsEnabled,
        bool $tcpScanEnabled,
        bool $dicomCheckEnabled,
        bool $scanUnresponsiveHosts,
        int $timeoutSeconds,
        array $portNumbers,
        array $dicomCandidatePorts,
        array $callingAeTitles,
        array $calledAeTitles,
        array $webPorts,
        array $databasePorts,
        HostDiscoveryService $hostDiscoveryService,
        PortScanService $portScanService,
        DicomEchoScanService $echoScanService,
        ClassificationService $classificationService,
    ): void {
        $pingResults = $pingEnabled
            ? $hostDiscoveryService->pingBatch($batch, $timeoutSeconds)
            : array_fill_keys($batch, ['reachable' => false, 'latency_ms' => null, 'error' => null]);

        $portScanTargets = [];
        foreach ($batch as $ip) {
            $reachable = $pingResults[$ip]['reachable'] ?? false;
            if ($tcpScanEnabled && $portNumbers !== [] && ($reachable || ! $pingEnabled || $scanUnresponsiveHosts)) {
                $portScanTargets[$ip] = $portNumbers;
            }
        }

        $portResults = $portScanTargets !== [] ? $portScanService->scanMany($portScanTargets, $timeoutSeconds) : [];

        foreach ($batch as $ip) {
            $reachablePing = $pingResults[$ip]['reachable'] ?? false;
            $hostPorts = $portResults[$ip] ?? [];
            $anyPortOpen = array_any($hostPorts, static fn (array $result): bool => $result['open']);
            $reachable = $reachablePing || $anyPortOpen;

            $hostname = null;
            if ($reverseDnsEnabled && ($reachable || $scanUnresponsiveHosts)) {
                $hostname = $hostDiscoveryService->reverseDns($ip);
            }

            if (! $reachable && $hostname === null && ! $scanUnresponsiveHosts) {
                continue;
            }

            $host = DiscoveredHost::query()->updateOrCreate(
                ['discovery_run_id' => $run->id, 'ip_address' => $ip],
                [
                    'hostname' => $hostname,
                    'reverse_dns' => $hostname,
                    'is_reachable' => $reachable,
                    'ping_latency_ms' => $pingResults[$ip]['latency_ms'] ?? null,
                    'last_seen_at' => now(),
                    'status' => DiscoveredHost::STATUS_DISCOVERED,
                ],
            );

            $openCandidatePorts = [];
            foreach ($hostPorts as $port => $result) {
                $isDicomCandidate = in_array($port, $dicomCandidatePorts, true);
                $host->ports()->updateOrCreate(
                    ['port' => $port, 'protocol' => 'tcp'],
                    [
                        'is_open' => $result['open'],
                        'is_dicom_candidate' => $isDicomCandidate,
                        'response_time_ms' => $result['response_time_ms'],
                    ],
                );
                if ($result['open'] && $isDicomCandidate) {
                    $openCandidatePorts[] = $port;
                }
            }

            $successfulEchoes = [];
            if ($dicomCheckEnabled && $openCandidatePorts !== [] && $calledAeTitles !== []) {
                $targets = [];
                foreach ($openCandidatePorts as $port) {
                    foreach ($calledAeTitles as $calledAe) {
                        foreach ($callingAeTitles as $callingAe) {
                            $targets[] = new DiscoveryEchoTarget($ip, $port, $callingAe, $calledAe);
                        }
                    }
                }

                $echoResults = $echoScanService->testMany($targets, config('discovery.timeouts.dicom_echo_seconds', 5));

                foreach ($echoResults as $result) {
                    $host->dicomResults()->create([
                        'port' => $result->target->port,
                        'calling_ae' => $result->target->callingAeTitle,
                        'called_ae' => $result->target->calledAeTitle,
                        'association_successful' => $result->associationSuccessful,
                        'echo_successful' => $result->echoSuccessful,
                        'error_code' => $result->errorCode,
                        'error_message' => $result->errorMessage,
                        'raw_response' => ['output' => $result->rawOutput],
                        'response_time_ms' => $result->durationMs,
                    ]);

                    if ($result->echoSuccessful) {
                        $successfulEchoes[] = ['port' => $result->target->port, 'called_ae' => $result->target->calledAeTitle];
                    }
                }
            }

            $existingRegistryMatch = System::query()->active()->where('ip_address', $ip)->exists()
                || ($hostname !== null && System::query()->active()->whereRaw('lower(hostname) = lower(?)', [$hostname])->exists())
                || DicomNode::query()->active()->where('host', $ip)->exists();

            $context = new ClassificationContext(
                hostname: $hostname,
                openDicomCandidatePorts: $openCandidatePorts,
                successfulEchoes: $successfulEchoes,
                existingRegistryMatch: $existingRegistryMatch,
                webPortOpen: array_any($webPorts, static fn (int $port): bool => ($hostPorts[$port]['open'] ?? false)),
                databasePortOpen: array_any($databasePorts, static fn (int $port): bool => ($hostPorts[$port]['open'] ?? false)),
            );

            $classification = $classificationService->classify($host, $context);

            $host->classificationEvidence()->delete();
            foreach ($classification->evidence as $evidence) {
                $host->classificationEvidence()->create([
                    'rule_name' => $evidence->ruleName,
                    'reason' => $evidence->reason,
                    'weight' => $evidence->weight,
                ]);
            }

            $host->update([
                'confidence_score' => $classification->confidenceLevel,
                'confidence_percentage' => $classification->percentage,
                'suggested_system_type' => $classification->suggestedSystemType,
            ]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $run = DiscoveryRun::query()->find($this->discoveryRunId);
        $run?->update([
            'status' => DiscoveryRun::STATUS_FAILED,
            'finished_at' => now(),
            'error_message' => mb_substr($exception?->getMessage() ?? 'Unbekannter Fehler.', 0, 2000),
        ]);
    }
}
