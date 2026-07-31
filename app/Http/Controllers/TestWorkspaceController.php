<?php

namespace App\Http\Controllers;

use App\Models\DicomNode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class TestWorkspaceController extends Controller
{
    public function __invoke(Request $request): Response
    {
        Gate::authorize('viewAny', DicomNode::class);

        $nodeModels = DicomNode::query()
            ->active()
            ->with([
                'system:id,public_id,organization_id,site_id,department_id,name,system_type,status',
                'system.organization:id,public_id,name',
                'system.site:id,public_id,name',
                'system.department:id,public_id,name',
            ])
            ->orderBy('name')
            ->get([
                'id',
                'public_id',
                'system_id',
                'name',
                'ae_title',
                'host',
                'port',
                'role',
                'status',
                'tls_enabled',
                'supports_echo',
                'supports_store',
                'supports_query',
                'supports_retrieve',
                'supports_worklist',
                'last_verified_at',
                'last_verification_status',
                'last_verification_duration_ms',
                'last_verification_message',
            ]);

        /** @var list<array<string, mixed>> $nodes */
        $nodes = [];

        foreach ($nodeModels as $node) {
            $nodes[] = [
                'public_id' => $node->public_id,
                'name' => $node->name,
                'ae_title' => $node->ae_title,
                'host' => $node->host,
                'port' => $node->port,
                'role' => $node->role,
                'status' => $node->status,
                'tls_enabled' => $node->tls_enabled,
                'supports_echo' => $node->supports_echo,
                'supports_store' => $node->supports_store,

                'supports_query' => $node->supports_query,
                'supports_retrieve' => $node->supports_retrieve,

                'supports_worklist' => $node->supports_worklist,
                'last_verified_at' => $node->last_verified_at?->toIso8601String(),
                'last_verification_status' => $node->last_verification_status,
                'last_verification_duration_ms' => $node->last_verification_duration_ms,
                'last_verification_message' => $node->last_verification_message,
                'system' => [
                    'public_id' => $node->system->public_id,
                    'name' => $node->system->name,
                    'system_type' => $node->system->system_type,
                    'status' => $node->system->status,
                    'organization' => $node->system->organization
                        ? [
                            'public_id' => $node->system->organization->public_id,
                            'name' => $node->system->organization->name,
                        ]
                        : null,
                    'site' => $node->system->site
                        ? [
                            'public_id' => $node->system->site->public_id,
                            'name' => $node->system->site->name,
                        ]
                        : null,
                    'department' => $node->system->department
                        ? [
                            'public_id' => $node->system->department->public_id,
                            'name' => $node->system->department->name,
                        ]
                        : null,
                ],
            ];
        }

        return Inertia::render('Tests/Index', [
            'nodes' => $nodes,
            'canRunEcho' => $request->user()?->hasPermission('registry.manage') ?? false,
            'canRunNetwork' => $request->user()?->hasPermission('registry.manage') ?? false,
            'latestResult' => $request->session()->get('diagnosticResult'),
        ]);
    }
}
