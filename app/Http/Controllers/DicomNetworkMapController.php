<?php

namespace App\Http\Controllers;

use App\Models\DicomConnection;
use App\Models\DicomNode;
use App\Models\System;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class DicomNetworkMapController extends Controller
{
    public function __invoke(Request $request): Response
    {
        Gate::authorize('viewAny', System::class);

        $organizationId = $request->integer('organization');
        $siteId = $request->integer('site');
        $departmentId = $request->integer('department');
        $service = trim((string) $request->query('service', ''));

        $nodes = DicomNode::query()
            ->active()
            ->with([
                'system:id,public_id,organization_id,site_id,department_id,name,system_type,status',
                'system.organization:id,public_id,name',
                'system.site:id,public_id,name',
                'system.department:id,public_id,name',
            ])
            ->whereHas(
                'system',
                function ($query) use (
                    $organizationId,
                    $siteId,
                    $departmentId,
                ): void {
                    $query
                        ->whereNull('archived_at')
                        ->when(
                            $organizationId > 0,
                            function ($systemQuery) use (
                                $organizationId,
                            ): void {
                                $systemQuery->where(
                                    'organization_id',
                                    $organizationId,
                                );
                            },
                        )
                        ->when(
                            $siteId > 0,
                            function ($systemQuery) use (
                                $siteId,
                            ): void {
                                $systemQuery->where(
                                    'site_id',
                                    $siteId,
                                );
                            },
                        )
                        ->when(
                            $departmentId > 0,
                            function ($systemQuery) use (
                                $departmentId,
                            ): void {
                                $systemQuery->where(
                                    'department_id',
                                    $departmentId,
                                );
                            },
                        );
                },
            )
            ->orderBy('name')
            ->get()
            ->map(static function (DicomNode $node): array {
                return [
                    'id' => $node->id,
                    'public_id' => $node->public_id,
                    'name' => $node->name,
                    'ae_title' => $node->ae_title,
                    'host' => $node->host,
                    'port' => $node->port,
                    'role' => $node->role,
                    'status' => $node->status,
                    'tls_enabled' => $node->tls_enabled,
                    'last_verified_at' => $node->last_verified_at
                        ?->toIso8601String(),
                    'last_verification_status' => $node->last_verification_status,
                    'last_verification_duration_ms' => $node->last_verification_duration_ms,
                    'system' => [
                        'public_id' => $node->system->public_id,
                        'name' => $node->system->name,
                        'system_type' => $node->system->system_type,
                        'status' => $node->system->status,
                        'organization' => $node->system->organization?->name,
                        'site' => $node->system->site?->name,
                        'department' => $node->system->department?->name,
                    ],
                ];
            })
            ->values();

        $nodeIds = $nodes
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $connections = DicomConnection::query()
            ->active()
            ->with([
                'sourceNode:id,public_id,name,ae_title',
                'targetNode:id,public_id,name,ae_title',
                'destinationNode:id,public_id,name,ae_title',
            ])
            ->whereIn('source_dicom_node_id', $nodeIds)
            ->whereIn('target_dicom_node_id', $nodeIds)
            ->when(
                $service !== '',
                function ($query) use ($service): void {
                    $query->where('service', $service);
                },
            )
            ->orderBy('name')
            ->get()
            ->map(
                static function (
                    DicomConnection $connection,
                ): array {
                    return [
                        'public_id' => $connection->public_id,
                        'name' => $connection->name,
                        'service' => $connection->service,
                        'status' => $connection->status,
                        'evidence_status' => $connection->evidence_status,
                        'source_node_id' => $connection->source_dicom_node_id,
                        'target_node_id' => $connection->target_dicom_node_id,
                        'destination_node_id' => $connection
                            ->destination_dicom_node_id,
                        'calling_ae_title' => $connection->calling_ae_title
                            ?? $connection->sourceNode->ae_title,
                        'called_ae_title' => $connection->called_ae_title
                            ?? $connection->targetNode->ae_title,
                        'port' => $connection->port_override,
                        'tls_enabled' => $connection->tls_enabled,
                        'test_enabled' => $connection->test_enabled,
                    ];
                },
            )
            ->values();

        return Inertia::render('Network/Index', [
            'nodes' => $nodes,
            'connections' => $connections,

            'filters' => [
                'organization' => $organizationId > 0
                    ? $organizationId
                    : null,
                'site' => $siteId > 0
                    ? $siteId
                    : null,
                'department' => $departmentId > 0
                    ? $departmentId
                    : null,
                'service' => $service,
            ],

            'summary' => [
                'systems' => $nodes
                    ->pluck('system.public_id')
                    ->unique()
                    ->count(),
                'nodes' => $nodes->count(),
                'connections' => $connections->count(),
                'failed_nodes' => $nodes
                    ->whereNotNull(
                        'last_verification_status',
                    )
                    ->where(
                        'last_verification_status',
                        '!=',
                        'success',
                    )
                    ->count(),
                'unverified_nodes' => $nodes
                    ->whereNull('last_verified_at')
                    ->count(),
            ],

            'services' => [
                ['value' => 'echo', 'label' => 'C-ECHO'],
                ['value' => 'store', 'label' => 'C-STORE'],
                ['value' => 'worklist', 'label' => 'Worklist'],
                ['value' => 'query', 'label' => 'Query'],
                ['value' => 'move', 'label' => 'C-MOVE'],
                ['value' => 'get', 'label' => 'C-GET'],
            ],
            'focusNodePublicId' => $request->query('node'),
            'focusConnectionPublicId' => $request->query('connection'),
        ]);
    }
}
