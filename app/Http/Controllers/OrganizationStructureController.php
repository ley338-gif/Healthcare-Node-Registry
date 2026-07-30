<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DicomNode;
use App\Models\Organization;
use App\Models\Site;
use App\Models\System;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

final class OrganizationStructureController extends Controller
{
    public function __invoke(): Response
    {
        $organizations = Organization::query()
            ->active()
            ->with([
                'sites' => fn ($query) => $query
                    ->active()
                    ->with([
                        'departments' => fn ($departmentQuery) => $departmentQuery
                            ->active()
                            ->orderBy('name'),
                    ])
                    ->orderBy('name'),
            ])
            ->orderBy('name')
            ->get([
                'id',
                'public_id',
                'name',
                'short_name',
                'description',
                'updated_at',
            ]);

        $systems = System::query()
            ->active()
            ->with([
                'dicomNodes' => fn ($query) => $query
                    ->active()
                    ->withCount([
                        'outgoingConnections',
                        'incomingConnections',
                    ]),
            ])
            ->orderBy('name')
            ->get([
                'id',
                'public_id',
                'organization_id',
                'site_id',
                'department_id',
                'name',
                'system_type',
                'status',
                'hostname',
                'ip_address',
                'vendor',
                'product',
            ])
            ->map(function (System $system): array {
                /** @var Collection<int, DicomNode> $nodes */
                $nodes = $system->dicomNodes;

                return [
                    'public_id' => $system->public_id,
                    'organization_id' => $system->organization_id,
                    'site_id' => $system->site_id,
                    'department_id' => $system->department_id,
                    'name' => $system->name,
                    'system_type' => $system->system_type,
                    'status' => $system->status,
                    'hostname' => $system->hostname,
                    'ip_address' => $system->ip_address,
                    'vendor' => $system->vendor,
                    'product' => $system->product,
                    'dicom_nodes_count' => $nodes->count(),
                    'connection_count' => $nodes->sum(
                        fn ($node): int => (int) $node->outgoing_connections_count
                            + (int) $node->incoming_connections_count,
                    ),
                    'verified_nodes_count' => $nodes
                        ->filter(fn ($node): bool => $node->last_verified_at !== null)
                        ->count(),
                    'latest_verified_at' => $nodes
                        ->max('last_verified_at')
                        ?->toIso8601String(),
                ];
            })
            ->values();

        return Inertia::render('OrganizationStructure/Index', [
            'summary' => [
                'organizations' => Organization::query()->active()->count(),
                'sites' => Site::query()->active()->count(),
                'departments' => Department::query()->active()->count(),
            ],
            'organizations' => $organizations,
            'systems' => $systems,
        ]);
    }
}
