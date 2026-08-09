<?php

namespace App\Services\Discovery;

use App\Models\DiscoveredHost;
use App\Models\DiscoveryRun;
use Illuminate\Database\Eloquent\Builder;

final class DiscoveryHostQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<DiscoveredHost>
     */
    public function build(DiscoveryRun $run, array $filters): Builder
    {
        return DiscoveredHost::query()
            ->where('discovery_run_id', $run->id)
            ->with(['ports', 'dicomResults', 'classificationEvidence', 'system:id,public_id,name'])
            ->when(($filters['reachable_only'] ?? null) === '1', fn (Builder $query) => $query->where('is_reachable', true))
            ->when(($filters['dicom_candidates_only'] ?? null) === '1', fn (Builder $query) => $query->whereHas('ports', fn (Builder $ports) => $ports->where('is_dicom_candidate', true)->where('is_open', true)))
            ->when(($filters['successful_echo_only'] ?? null) === '1', fn (Builder $query) => $query->whereHas('dicomResults', fn (Builder $results) => $results->where('echo_successful', true)))
            ->when(filled($filters['status'] ?? null), fn (Builder $query) => $query->where('status', $filters['status']))
            ->when(filled($filters['confidence'] ?? null), fn (Builder $query) => $query->where('confidence_score', $filters['confidence']))
            ->when(filled($filters['system_type'] ?? null), fn (Builder $query) => $query->where('suggested_system_type', $filters['system_type']))
            ->when(filled($filters['port'] ?? null), fn (Builder $query) => $query->whereHas('ports', fn (Builder $ports) => $ports->where('port', $filters['port'])))
            ->orderByDesc('confidence_percentage')
            ->orderBy('ip_address');
    }
}
