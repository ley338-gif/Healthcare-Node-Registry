<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\Controller;
use App\Models\DiscoveryRun;
use App\Services\Discovery\DiscoveryRunComparisonService;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class DiscoveryRunComparisonController extends Controller
{
    public function __invoke(
        DiscoveryRun $discoveryRun,
        DiscoveryRun $baselineRun,
        DiscoveryRunComparisonService $comparisonService,
    ): Response {
        Gate::authorize('view', $discoveryRun);
        Gate::authorize('view', $baselineRun);

        abort_if($discoveryRun->is($baselineRun), 422, 'Ein Discovery-Lauf kann nicht mit sich selbst verglichen werden.');
        abort_unless($discoveryRun->status === DiscoveryRun::STATUS_COMPLETED, 422, 'Der aktuelle Discovery-Lauf ist noch nicht vollständig abgeschlossen.');
        abort_unless($baselineRun->status === DiscoveryRun::STATUS_COMPLETED, 422, 'Der Ausgangslauf ist noch nicht vollständig abgeschlossen.');
        abort_unless($discoveryRun->ip_range === $baselineRun->ip_range, 422, 'Discovery-Läufe mit unterschiedlichen Zielbereichen sind nicht vergleichbar.');

        $comparison = $comparisonService->compare($baselineRun, $discoveryRun);

        return Inertia::render('Discovery/Runs/Compare', [
            'currentRun' => $this->presentRun($discoveryRun),
            'baselineRun' => $this->presentRun($baselineRun),
            'summary' => $comparison['summary'],
            'results' => $comparison['results'],
        ]);
    }

    /** @return array<string, mixed> */
    private function presentRun(DiscoveryRun $run): array
    {
        return [
            'public_id' => $run->public_id,
            'name' => $run->name,
            'ip_range' => $run->ip_range,
            'finished_at' => $run->finished_at?->toIso8601String(),
            'found_hosts_count' => $run->found_hosts_count,
            'dicom_candidates_count' => $run->dicom_candidates_count,
        ];
    }
}
