<?php

namespace App\Services\Discovery;

use App\Jobs\RunDiscoveryScanJob;
use App\Models\DiscoveryRun;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Orchestriert das Anlegen und Starten eines Discovery-Laufs aus dem
 * Wizard-Payload sowie den kooperativen Abbruch laufender Läufe.
 */
final class DiscoveryRunService
{
    public function __construct(
        private readonly NetworkRangeService $rangeService,
        private readonly DiscoveryAuditService $auditService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createRun(array $data, ?User $actor): DiscoveryRun
    {
        $run = DB::transaction(function () use ($data): DiscoveryRun {
            $excludeIps = $data['exclude_ips'] ?? [];
            $totalIps = count($this->rangeService->expand($data['ip_range'], $excludeIps));

            $run = DiscoveryRun::query()->create([
                'name' => $data['name'],
                'location' => $data['location'] ?? null,
                'department' => $data['department'] ?? null,
                'ip_range' => $data['ip_range'],
                'exclude_ips' => $excludeIps,
                'status' => DiscoveryRun::STATUS_PENDING,
                'total_ips' => $totalIps,
                'scan_options' => $data['scan_options'],
                'created_by' => $data['created_by'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            foreach ($excludeIps as $ip) {
                $run->exclusions()->create(['ip_address' => $ip]);
            }

            foreach ($data['ports'] as $port) {
                $run->ports()->create($port);
            }

            foreach ($data['ae_candidates'] as $candidate) {
                $run->aeCandidates()->create($candidate);
            }

            return $run;
        });

        $this->auditService->runStarted($run, $actor);
        RunDiscoveryScanJob::dispatch($run->id)->onQueue('discovery');

        return $run;
    }

    public function cancel(DiscoveryRun $run, ?User $actor): void
    {
        if (! $run->isActive()) {
            return;
        }

        $run->update(['status' => DiscoveryRun::STATUS_CANCELLING]);
        $this->auditService->runCancelled($run, $actor);
    }
}
