<?php

namespace App\Http\Controllers;

use App\Models\DiagnosticTestProfile;
use App\Models\DiagnosticTestRun;
use App\Models\DicomNode;
use App\Models\User;
use App\Support\DiagnosticPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class TestWorkspaceController extends Controller
{
    public function __invoke(Request $request): Response
    {
        Gate::authorize('viewAny', DicomNode::class);
        Gate::authorize('viewAny', DiagnosticTestRun::class);
        Gate::authorize('viewAny', DiagnosticTestProfile::class);

        $filters = $request->validate([
            'history_from' => ['nullable', 'date_format:Y-m-d'],
            'history_to' => ['nullable', 'date_format:Y-m-d'],
            'history_node' => ['nullable', 'uuid'],
            'history_type' => ['nullable', 'string', 'max:60'],
            'history_status' => ['nullable', 'string', 'max:30'],
            'history_user' => ['nullable', 'uuid'],
            'run' => ['nullable', 'uuid'],
            'node' => ['nullable', 'uuid'],
            'service' => ['nullable', 'string', 'in:echo,store,worklist,query'],
            'calling_ae_title' => ['nullable', 'string', 'max:16'],
            'called_ae_title' => ['nullable', 'string', 'max:16'],
        ]);

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
                'modality',
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
                'supports_mpps',
                'supports_storage_commitment',
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
                'modality' => $node->modality,
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
                'supports_mpps' => $node->supports_mpps,
                'supports_storage_commitment' => $node->supports_storage_commitment,
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

        $canViewUsers = $request->user()?->hasPermission('registry.manage') ?? false;
        $history = DiagnosticTestRun::query()
            ->with([
                'dicomNode:id,public_id,name',
                'system:id,public_id,name',
                'user:id,public_id,name',
            ])
            ->when(
                isset($filters['history_from']),
                fn ($query) => $query->whereDate('started_at', '>=', $filters['history_from']),
            )
            ->when(
                isset($filters['history_to']),
                fn ($query) => $query->whereDate('started_at', '<=', $filters['history_to']),
            )
            ->when(
                isset($filters['history_node']),
                fn ($query) => $query->whereHas(
                    'dicomNode',
                    fn ($nodeQuery) => $nodeQuery->where('public_id', $filters['history_node']),
                ),
            )
            ->when(
                isset($filters['history_type']),
                fn ($query) => $query->where('test_type', $filters['history_type']),
            )
            ->when(
                isset($filters['history_status']),
                fn ($query) => $query->where('status', $filters['history_status']),
            )
            ->when(
                $canViewUsers && isset($filters['history_user']),
                fn ($query) => $query->whereHas(
                    'user',
                    fn ($userQuery) => $userQuery->where('public_id', $filters['history_user']),
                ),
            )
            ->when(isset($filters['run']), fn ($query) => $query->orderByRaw('CASE WHEN public_id = ? THEN 0 ELSE 1 END', [$filters['run']]))
            ->latest('started_at')
            ->paginate(10, ['*'], 'history_page')
            ->withQueryString()
            ->through(static fn (DiagnosticTestRun $run): array => [
                'public_id' => $run->public_id,
                'test_type' => $run->test_type,
                'status' => $run->status,
                'started_at' => $run->started_at->toIso8601String(),
                'finished_at' => $run->finished_at->toIso8601String(),
                'duration_ms' => $run->duration_ms,
                'result_count' => $run->result_count,
                'summary' => $run->summary,
                'steps' => $run->steps,
                'details' => $run->details,
                'warnings' => $run->warnings,
                'errors' => $run->errors,
                'dicom_node' => [
                    'public_id' => $run->dicomNode->public_id,
                    'name' => $run->dicomNode->name,
                ],
                'system' => [
                    'public_id' => $run->system->public_id,
                    'name' => $run->system->name,
                ],
                'user' => $run->user
                    ? [
                        'public_id' => $run->user->public_id,
                        'name' => $run->user->name,
                    ]
                    : null,
            ]);

        return Inertia::render('Tests/Index', [
            'nodes' => $nodes,
            'canRunEcho' => $request->user()?->can(DiagnosticPermission::Echo->value) ?? false,
            'canRunNetwork' => $request->user()?->can(DiagnosticPermission::Echo->value) ?? false,
            'canRunWorklist' => $request->user()?->can(DiagnosticPermission::Worklist->value) ?? false,
            'canRunMpps' => $request->user()?->can(DiagnosticPermission::Mpps->value) ?? false,
            'canRunPacsQuery' => $request->user()?->can(DiagnosticPermission::Query->value) ?? false,
            'canRunStorage' => $request->user()?->can(DiagnosticPermission::Store->value) ?? false,
            'canRunStorageCommitment' => $request->user()?->can(DiagnosticPermission::StorageCommitment->value) ?? false,
            'canRunCapabilityMatrix' => $request->user()?->can(DiagnosticPermission::CapabilityMatrix->value) ?? false,
            'canAnalyzeFile' => $request->user()?->hasPermission('tests.analyze_file') ?? false,
            'canExport' => $request->user()?->hasPermission('tests.export') ?? false,
            'fileAnalysis' => $request->session()->get('dicomFileAnalysis'),
            'latestResult' => $request->session()->get('diagnosticResult'),
            'history' => $history,
            'historyFilters' => $filters,
            'historyUsers' => $canViewUsers
                ? User::query()->orderBy('name')->get(['public_id', 'name'])
                : [],
            'profiles' => DiagnosticTestProfile::query()
                ->whereNull('archived_at')
                ->with('dicomNode:id,public_id,name')
                ->orderBy('name')
                ->get()
                ->map(static fn (DiagnosticTestProfile $profile): array => [
                    'public_id' => $profile->public_id,
                    'name' => $profile->name,
                    'description' => $profile->description,
                    'test_type' => $profile->test_type,
                    'calling_ae_title' => $profile->calling_ae_title,
                    'configuration' => $profile->configuration,
                    'timeout_seconds' => $profile->timeout_seconds,
                    'enabled' => $profile->enabled,
                    'dicom_node' => [
                        'public_id' => $profile->dicomNode->public_id,
                        'name' => $profile->dicomNode->name,
                    ],
                ]),
            'canManageProfiles' => $request->user()?->hasPermission('registry.manage') ?? false,
            'defaultCallingAeTitle' => (string) config('diagnostics.default_calling_ae_title'),
            'connectionPrefill' => [
                'node' => $filters['node'] ?? null,
                'service' => $filters['service'] ?? null,
                'calling_ae_title' => $filters['calling_ae_title'] ?? null,
                'called_ae_title' => $filters['called_ae_title'] ?? null,
            ],
        ]);
    }
}
