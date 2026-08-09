<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDiscoveryRunRequest;
use App\Models\DiscoveredHost;
use App\Models\DiscoveryRun;
use App\Services\Discovery\DiscoveryHostQuery;
use App\Services\Discovery\DiscoveryResultExporter;
use App\Services\Discovery\DiscoveryRunService;
use App\Services\Registry\SystemReferenceDataService;
use App\Support\RegistryAudit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DiscoveryRunController extends Controller
{
    public function store(StoreDiscoveryRunRequest $request, DiscoveryRunService $service): RedirectResponse
    {
        $run = $service->createRun([...$request->validated(), 'created_by' => $request->user()?->id], $request->user());

        return redirect()->route('discovery.runs.show', $run)->with('success', "Discovery-Lauf \"{$run->name}\" wurde gestartet.");
    }

    public function show(
        Request $request,
        DiscoveryRun $discoveryRun,
        SystemReferenceDataService $referenceData,
        DiscoveryHostQuery $hostQuery,
    ): Response {
        Gate::authorize('view', $discoveryRun);

        $filters = $this->filters($request);

        return Inertia::render('Discovery/Runs/Show', [
            'run' => $this->presentRun($discoveryRun),
            'hosts' => $hostQuery->build($discoveryRun, $filters)->paginate(25)->withQueryString()->through(fn (DiscoveredHost $host) => $this->presentHost($host)),
            'filters' => $filters,
            'systemTypes' => $referenceData->typeOptions(),
            'canReview' => $request->user()?->hasPermission('discovery.manage') ?? false,
            'canCancel' => $request->user()?->can('cancel', $discoveryRun) ?? false,
        ]);
    }

    public function export(
        Request $request,
        DiscoveryRun $discoveryRun,
        string $format,
        DiscoveryHostQuery $hostQuery,
        DiscoveryResultExporter $exporter,
        RegistryAudit $audit,
    ): StreamedResponse {
        Gate::authorize('view', $discoveryRun);
        abort_unless(in_array($format, ['csv', 'json'], true), 404);

        $filters = $this->filters($request);
        $audit->record('discovery.results-exported', $discoveryRun, $request->user(), [
            'format' => $format,
            'filters' => array_filter($filters, filled(...)),
        ]);

        $filename = 'discovery-'.$discoveryRun->public_id.'-'.now()->format('Y-m-d-His').'.'.$format;

        return response()->streamDownload(function () use ($discoveryRun, $filters, $format, $hostQuery, $exporter): void {
            $stream = fopen('php://output', 'w');
            abort_if($stream === false, 500);
            $hosts = $hostQuery->build($discoveryRun, $filters)->lazy(250);

            if ($format === 'csv') {
                $exporter->writeCsv($stream, $hosts);
            } else {
                $exporter->writeJson($stream, $discoveryRun, $filters, $hosts);
            }
        }, $filename, [
            'Content-Type' => $format === 'csv' ? 'text/csv; charset=UTF-8' : 'application/json; charset=UTF-8',
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

    /** @return array<string, mixed> */
    private function filters(Request $request): array
    {
        return $request->only(['reachable_only', 'dicom_candidates_only', 'successful_echo_only', 'status', 'confidence', 'system_type', 'port']);
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
