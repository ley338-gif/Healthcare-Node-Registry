<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\Controller;
use App\Http\Requests\PromoteDiscoveredHostRequest;
use App\Models\DiscoveredHost;
use App\Models\System;
use App\Services\Discovery\DiscoveryAuditService;
use App\Services\Discovery\RegistryPromotionService;
use Illuminate\Http\RedirectResponse;

final class DiscoveryPromotionController extends Controller
{
    public function store(
        PromoteDiscoveredHostRequest $request,
        DiscoveredHost $discoveredHost,
        RegistryPromotionService $promotionService,
        DiscoveryAuditService $auditService,
    ): RedirectResponse {
        $data = $request->validated();

        $existingSystem = $data['action'] === 'update_existing'
            ? System::query()->find($data['existing_system_id'])
            : null;

        $systemData = [
            'organization_id' => $data['organization_id'],
            'site_id' => $data['site_id'] ?? null,
            'department_id' => $data['department_id'] ?? null,
            'name' => $data['name'],
            'system_type' => $data['system_type'],
            'status' => $data['operational_status'],
            'hostname' => $discoveredHost->hostname,
            'ip_address' => $discoveredHost->ip_address,
            'vendor' => $data['vendor'] ?? null,
            'model' => $data['model'] ?? null,
            'description' => $data['description'] ?? null,
            'notes' => $data['notes'] ?? null,
            'responsible' => $data['responsible'] ?? null,
            'criticality' => $data['criticality'] ?? null,
        ];

        $dicomNodeData = [
            'ae_title' => $data['ae_title'],
            'host' => $discoveredHost->hostname ?: $discoveredHost->ip_address,
            'port' => $data['port'],
            'role' => $data['role'],
            'name' => $data['dicom_node_name'] ?? null,
        ];

        $system = $promotionService->promote(
            host: $discoveredHost,
            systemData: $systemData,
            dicomNodeData: $dicomNodeData,
            existingSystem: $existingSystem,
            discoveryRunId: $discoveredHost->discovery_run_id,
            originalConfidencePercentage: $discoveredHost->confidence_percentage,
        );

        $auditService->systemPromoted($system, $discoveredHost, $request->user());

        return redirect()->route('systems.show', $system)->with('success', "System \"{$system->name}\" wurde aus Discovery übernommen.");
    }
}
