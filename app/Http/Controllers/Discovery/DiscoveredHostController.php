<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DiscoveredHost;
use App\Models\Organization;
use App\Models\Site;
use App\Models\System;
use App\Services\Discovery\Classification\ClassificationContext;
use App\Services\Discovery\Classification\ClassificationService;
use App\Services\Discovery\DicomEchoScanService;
use App\Services\Discovery\DiscoveryAuditService;
use App\Services\Discovery\DiscoveryEchoTarget as EchoTarget;
use App\Services\Discovery\PortScanService;
use App\Services\Discovery\RegistryPromotionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class DiscoveredHostController extends Controller
{
    public function confirm(Request $request, DiscoveredHost $discoveredHost, DiscoveryAuditService $audit): RedirectResponse
    {
        Gate::authorize('review', $discoveredHost);

        $discoveredHost->update(['status' => DiscoveredHost::STATUS_CONFIRMED]);
        $audit->findingConfirmed($discoveredHost, $request->user());

        return back()->with('success', 'Fund wurde als bestätigt markiert.');
    }

    public function ignore(Request $request, DiscoveredHost $discoveredHost, DiscoveryAuditService $audit): RedirectResponse
    {
        Gate::authorize('review', $discoveredHost);

        $discoveredHost->update(['status' => DiscoveredHost::STATUS_IGNORED]);
        $audit->findingIgnored($discoveredHost, $request->user());

        return back()->with('success', 'Fund wurde ignoriert.');
    }

    public function retest(
        DiscoveredHost $discoveredHost,
        PortScanService $portScanService,
        DicomEchoScanService $echoScanService,
        ClassificationService $classificationService,
    ): RedirectResponse {
        Gate::authorize('review', $discoveredHost);

        $discoveredHost->load('ports');
        $discoveryRun = $discoveredHost->discoveryRun;
        $timeoutSeconds = (int) (($discoveryRun->scan_options['timeout_seconds'] ?? null) ?: config('discovery.timeouts.tcp_connect_seconds', 2));
        $ports = $discoveredHost->ports->pluck('port')->map(fn ($port): int => (int) $port)->all();

        if ($ports !== []) {
            $scan = $portScanService->scanMany([$discoveredHost->ip_address => $ports], $timeoutSeconds);
            foreach ($scan[$discoveredHost->ip_address] ?? [] as $port => $result) {
                $discoveredHost->ports()->where('port', $port)->update([
                    'is_open' => $result['open'],
                    'response_time_ms' => $result['response_time_ms'],
                ]);
            }
        }

        $discoveredHost->refresh()->load('ports', 'dicomResults', 'classificationEvidence');
        $openCandidatePorts = $discoveredHost->ports->where('is_open', true)->where('is_dicom_candidate', true)->pluck('port')->map(fn ($port): int => (int) $port)->values()->all();

        $calledAeTitles = $discoveredHost->dicomResults->pluck('called_ae')->unique()->values()->all();
        if ($calledAeTitles === []) {
            $calledAeTitles = [config('discovery.default_calling_ae_title', 'HNR_DISCOVERY')];
        }
        $callingAe = config('discovery.default_calling_ae_title', 'HNR_DISCOVERY');

        $successfulEchoes = [];
        if ($openCandidatePorts !== []) {
            $targets = [];
            foreach ($openCandidatePorts as $port) {
                foreach ($calledAeTitles as $calledAe) {
                    $targets[] = new EchoTarget($discoveredHost->ip_address, $port, $callingAe, $calledAe);
                }
            }

            foreach ($echoScanService->testMany($targets, config('discovery.timeouts.dicom_echo_seconds', 5)) as $result) {
                $discoveredHost->dicomResults()->create([
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

        $context = new ClassificationContext(
            hostname: $discoveredHost->hostname,
            openDicomCandidatePorts: $openCandidatePorts,
            successfulEchoes: $successfulEchoes,
            existingRegistryMatch: System::query()->active()->where('ip_address', $discoveredHost->ip_address)->exists(),
            webPortOpen: $discoveredHost->ports->whereIn('port', config('discovery.web_ports', []))->where('is_open', true)->isNotEmpty(),
            databasePortOpen: $discoveredHost->ports->whereIn('port', config('discovery.database_ports', []))->where('is_open', true)->isNotEmpty(),
        );

        $classification = $classificationService->classify($discoveredHost, $context);
        $discoveredHost->classificationEvidence()->delete();
        foreach ($classification->evidence as $evidence) {
            $discoveredHost->classificationEvidence()->create(['rule_name' => $evidence->ruleName, 'reason' => $evidence->reason, 'weight' => $evidence->weight]);
        }

        $discoveredHost->update([
            'confidence_score' => $classification->confidenceLevel,
            'confidence_percentage' => $classification->percentage,
            'suggested_system_type' => $classification->suggestedSystemType,
            'status' => DiscoveredHost::STATUS_REVIEWING,
            'last_seen_at' => now(),
        ]);

        return back()->with('success', 'Erneute Prüfung abgeschlossen.');
    }

    public function promotionData(DiscoveredHost $discoveredHost, RegistryPromotionService $promotionService): JsonResponse
    {
        Gate::authorize('promote', $discoveredHost);

        $proposedName = $discoveredHost->hostname ?: $discoveredHost->ip_address;
        $successfulEcho = $discoveredHost->dicomResults()->where('echo_successful', true)->first();

        return response()->json([
            'organizations' => Organization::query()->orderBy('name')->get(['id', 'name']),
            'sites' => Site::query()->orderBy('name')->get(['id', 'organization_id', 'name']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'site_id', 'name']),
            'duplicates' => collect($promotionService->findDuplicates(
                $discoveredHost,
                $proposedName,
                $successfulEcho->called_ae ?? '',
                $successfulEcho->port ?? ($discoveredHost->ports()->where('is_dicom_candidate', true)->value('port') ?? 104),
            ))->map(fn (array $match) => [
                'type' => $match['type'],
                'system' => ['id' => $match['system']->id, 'public_id' => $match['system']->public_id, 'name' => $match['system']->name],
                'dicom_node' => $match['dicom_node'] ? ['ae_title' => $match['dicom_node']->ae_title, 'host' => $match['dicom_node']->host, 'port' => $match['dicom_node']->port] : null,
            ])->values(),
            'suggested' => [
                'name' => $proposedName,
                'system_type' => $discoveredHost->suggested_system_type,
                'ae_title' => $successfulEcho->called_ae ?? '',
                'port' => $successfulEcho->port ?? null,
            ],
        ]);
    }
}
