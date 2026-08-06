<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDiscoveryRunRequest;
use App\Models\DiscoveredHost;
use App\Models\DiscoveryRun;
use App\Services\Discovery\DiscoveryRunService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class DiscoveryRunController extends Controller
{
    public function store(StoreDiscoveryRunRequest $request, DiscoveryRunService $service): RedirectResponse
    {
        $run = $service->createRun([...$request->validated(), 'created_by' => $request->user()?->id], $request->user());

        return redirect()->route('discovery.runs.show', $run)->with('success', "Discovery-Lauf \"{$run->name}\" wurde gestartet.");
    }

    public function show(Request $request, DiscoveryRun $discoveryRun): Response
    {
        Gate::authorize('view', $discoveryRun);

        $filters = $request->only(['reachable_only', 'dicom_candidates_only', 'successful_echo_only', 'status', 'confidence', 'system_type', 'port']);

        $query = $discoveryRun->hosts()
            ->with(['ports', 'dicomResults', 'classificationEvidence', 'system:id,public_id,name'])
            ->when(($filters['reachable_only'] ?? null) === '1', fn ($q) => $q->where('is_reachable', true))
            ->when(($filters['dicom_candidates_only'] ?? null) === '1', fn ($q) => $q->whereHas('ports', fn ($p) => $p->where('is_dicom_candidate', true)->where('is_open', true)))
            ->when(($filters['successful_echo_only'] ?? null) === '1', fn ($q) => $q->whereHas('dicomResults', fn ($p) => $p->where('echo_successful', true)))
            ->when(filled($filters['status'] ?? null), fn ($q) => $q->where('status', $filters['status']))
            ->when(filled($filters['confidence'] ?? null), fn ($q) => $q->where('confidence_score', $filters['confidence']))
            ->when(filled($filters['system_type'] ?? null), fn ($q) => $q->where('suggested_system_type', $filters['system_type']))
            ->when(filled($filters['port'] ?? null), fn ($q) => $q->whereHas('ports', fn ($p) => $p->where('port', $filters['port'])))
            ->orderByDesc('confidence_percentage')
            ->orderBy('ip_address');

        return Inertia::render('Discovery/Runs/Show', [
            'run' => $this->presentRun($discoveryRun),
            'hosts' => $query->paginate(25)->withQueryString()->through(fn (DiscoveredHost $host) => $this->presentHost($host)),
            'filters' => $filters,
            'canReview' => $request->user()?->hasPermission('discovery.manage') ?? false,
            'canCancel' => $request->user()?->can('cancel', $discoveryRun) ?? false,
        ]);
    }

    public function status(DiscoveryRun $discoveryRun): JsonResponse
    {
        Gate::authorize('view', $discoveryRun);

        return response()->json($this->presentRun($discoveryRun));
    }

    public function cancel(Request $request, DiscoveryRun $discoveryRun, DiscoveryRunService $service): RedirectResponse
    {
        Gate::authorize('cancel', $discoveryRun);

        $service->cancel($discoveryRun, $request->user());

        return back()->with('success', 'Der Discovery-Lauf wird abgebrochen.');
    }

    /**
     * @return array<string, mixed>
     */
    private function presentRun(DiscoveryRun $run): array
    {
        return [
            'public_id' => $run->public_id,
            'name' => $run->name,
            'location' => $run->location,
            'department' => $run->department,
            'ip_range' => $run->ip_range,
            'status' => $run->status,
            'progress_percentage' => $run->progress_percentage,
            'total_ips' => $run->total_ips,
            'processed_ips' => $run->processed_ips,
            'found_hosts_count' => $run->found_hosts_count,
            'dicom_candidates_count' => $run->dicom_candidates_count,
            'started_at' => $run->started_at?->toIso8601String(),
            'finished_at' => $run->finished_at?->toIso8601String(),
            'error_message' => $run->error_message,
            'description' => $run->description,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentHost(DiscoveredHost $host): array
    {
        return [
            'public_id' => $host->public_id,
            'ip_address' => $host->ip_address,
            'hostname' => $host->hostname,
            'is_reachable' => $host->is_reachable,
            'ping_latency_ms' => $host->ping_latency_ms,
            'status' => $host->status,
            'confidence_score' => $host->confidence_score,
            'confidence_percentage' => $host->confidence_percentage,
            'suggested_system_type' => $host->suggested_system_type,
            'system' => $host->system ? ['public_id' => $host->system->public_id, 'name' => $host->system->name] : null,
            'last_seen_at' => $host->last_seen_at?->toIso8601String(),
            'ports' => $host->ports->map(fn ($port) => [
                'port' => $port->port,
                'protocol' => $port->protocol,
                'is_open' => $port->is_open,
                'is_dicom_candidate' => $port->is_dicom_candidate,
                'response_time_ms' => $port->response_time_ms,
            ])->values(),
            'dicom_results' => $host->dicomResults->map(fn ($result) => [
                'port' => $result->port,
                'calling_ae' => $result->calling_ae,
                'called_ae' => $result->called_ae,
                'association_successful' => $result->association_successful,
                'echo_successful' => $result->echo_successful,
                'error_code' => $result->error_code,
                'error_message' => $result->error_message,
                'response_time_ms' => $result->response_time_ms,
                'created_at' => $result->created_at?->toIso8601String(),
            ])->values(),
            'classification_evidence' => $host->classificationEvidence->map(fn ($evidence) => [
                'rule_name' => $evidence->rule_name,
                'reason' => $evidence->reason,
                'weight' => $evidence->weight,
            ])->values(),
        ];
    }
}
