<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSystemRequest;
use App\Http\Requests\UpdateSystemRequest;
use App\Models\Department;
use App\Models\DicomConnection;
use App\Models\DicomNode;
use App\Models\Organization;
use App\Models\Site;
use App\Models\System;
use App\Models\User;
use App\Services\Audit\RegistryHistoryService;
use App\Support\RegistryAudit;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class SystemController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', System::class);

        $search = trim((string) $request->query('search', ''));
        $type = trim((string) $request->query('type', ''));
        $status = trim((string) $request->query('status', ''));

        $organizationId = $request->integer('organization');
        $siteId = $request->integer('site');
        $departmentId = $request->integer('department');
        $selectedPublicId = trim((string) $request->query('selected', ''));

        $systems = System::query()
            ->with([
                'organization:id,public_id,name',
                'site:id,public_id,name',
                'department:id,public_id,name',
            ])
            ->withCount([
                'dicomNodes as dicom_nodes_count' => fn ($query) => $query
                    ->active(),
                'dicomNodes as failed_dicom_nodes_count' => fn ($query) => $query
                    ->active()
                    ->whereNotNull('last_verification_status')
                    ->where('last_verification_status', '!=', 'success'),
            ])
            ->whereNull('archived_at')
            ->when(
                $search !== '',
                fn ($query) => $query->where(
                    fn ($searchQuery) => $searchQuery
                        ->where('name', 'ilike', "%{$search}%")
                        ->orWhere('hostname', 'ilike', "%{$search}%")
                        ->orWhere('fqdn', 'ilike', "%{$search}%")
                        ->orWhere('ip_address', 'ilike', "%{$search}%")
                        ->orWhere('vendor', 'ilike', "%{$search}%")
                        ->orWhere('product', 'ilike', "%{$search}%"),
                ),
            )
            ->when(
                $type !== '',
                fn ($query) => $query->where('system_type', $type),
            )
            ->when(
                $status !== '',
                fn ($query) => $query->where('status', $status),
            )
            ->when(
                $organizationId > 0,
                fn ($query) => $query->where(
                    'organization_id',
                    $organizationId,
                ),
            )
            ->when(
                $siteId > 0,
                fn ($query) => $query->where('site_id', $siteId),
            )
            ->when(
                $departmentId > 0,
                fn ($query) => $query->where(
                    'department_id',
                    $departmentId,
                ),
            )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $visibleSystemIds = $systems
            ->getCollection()
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $topologyConnectionModels = DicomConnection::query()
            ->active()
            ->where(function ($query) use ($visibleSystemIds): void {
                $query
                    ->whereHas(
                        'sourceNode',
                        fn ($nodeQuery) => $nodeQuery->whereIn(
                            'system_id',
                            $visibleSystemIds,
                        ),
                    )
                    ->orWhereHas(
                        'targetNode',
                        fn ($nodeQuery) => $nodeQuery->whereIn(
                            'system_id',
                            $visibleSystemIds,
                        ),
                    );
            })
            ->get();

        $topologyNodeIds = $topologyConnectionModels
            ->flatMap(
                static fn (DicomConnection $connection): array => [
                    $connection->source_dicom_node_id,
                    $connection->target_dicom_node_id,
                ],
            )
            ->unique()
            ->values()
            ->all();

        $topologyNodeModels = DicomNode::query()
            ->active()
            ->where(function ($query) use (
                $visibleSystemIds,
                $topologyNodeIds,
            ): void {
                $query
                    ->whereIn('system_id', $visibleSystemIds)
                    ->when(
                        $topologyNodeIds !== [],
                        fn ($nodeQuery) => $nodeQuery->orWhereIn(
                            'id',
                            $topologyNodeIds,
                        ),
                    );
            })
            ->with([
                'system.organization:id,name',
                'system.site:id,name',
                'system.department:id,name',
            ])
            ->orderBy('name')
            ->get();

        $topologyNodes = $topologyNodeModels
            ->map(static fn (DicomNode $node): array => [
                'id' => $node->id,
                'public_id' => $node->public_id,
                'name' => $node->name,
                'ae_title' => $node->ae_title,
                'host' => $node->host,
                'port' => $node->port,
                'role' => $node->role,
                'status' => $node->status,
                'tls_enabled' => $node->tls_enabled,
                'last_verified_at' => $node->last_verified_at?->toIso8601String(),
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
            ])
            ->values();

        /** @var list<array<string, mixed>> $topologyConnections */
        $topologyConnections = [];

        foreach ($topologyConnectionModels as $connection) {
            $topologyConnections[] = [
                'public_id' => $connection->public_id,
                'name' => $connection->name,
                'service' => $connection->service,
                'status' => $connection->status,
                'source_node_id' => $connection->source_dicom_node_id,
                'target_node_id' => $connection->target_dicom_node_id,
                'destination_node_id' => $connection->destination_dicom_node_id,
                'calling_ae_title' => $connection->calling_ae_title ?? '',
                'called_ae_title' => $connection->called_ae_title ?? '',
                'port' => $connection->port_override,
                'tls_enabled' => $connection->tls_enabled,
                'test_enabled' => $connection->test_enabled,
            ];
        }

        $selectedSystem = System::query()
            ->whereNull('archived_at')
            ->when(
                $selectedPublicId !== '',
                fn ($query) => $query->where('public_id', $selectedPublicId),
                fn ($query) => $query->whereIn('id', $visibleSystemIds)->orderBy('name'),
            )
            ->with([
                'organization:id,public_id,name',
                'site:id,public_id,name',
                'department:id,public_id,name',
                'documentation' => fn ($query) => $query
                    ->with('updatedByUser:id,public_id,name')
                    ->orderBy('section'),
            ])
            ->first();

        $selectedDicomNodes = collect();
        $selectedDicomConnections = collect();
        $selectedDocumentation = collect();

        if ($selectedSystem !== null) {
            Gate::authorize('view', $selectedSystem);
            $selectedDocumentation = $selectedSystem->documentation;

            $selectedDicomNodes = $selectedSystem
                ->dicomNodes()
                ->active()
                ->with([
                    'verifications' => fn ($query) => $query
                        ->with('triggeredByUser:id,name')
                        ->latest('verified_at')
                        ->limit(20),
                ])
                ->orderBy('name')
                ->get();

            $selectedDicomConnections = DicomConnection::query()
                ->active()
                ->where(function ($query) use ($selectedSystem): void {
                    $query
                        ->whereHas(
                            'sourceNode',
                            fn ($nodeQuery) => $nodeQuery->where(
                                'system_id',
                                $selectedSystem->id,
                            ),
                        )
                        ->orWhereHas(
                            'targetNode',
                            fn ($nodeQuery) => $nodeQuery->where(
                                'system_id',
                                $selectedSystem->id,
                            ),
                        );
                })
                ->with([
                    'sourceNode:id,public_id,system_id,name,ae_title,host,port',
                    'sourceNode.system:id,public_id,name',
                    'targetNode:id,public_id,system_id,name,ae_title,host,port',
                    'targetNode.system:id,public_id,name',
                    'destinationNode:id,public_id,system_id,name,ae_title,host,port',
                    'destinationNode.system:id,public_id,name',
                ])
                ->orderBy('name')
                ->get();
        }

        return Inertia::render('Registry/Systems/Index', [
            'items' => $systems,
            'selectedSystem' => $selectedSystem,
            'dicomNodes' => $selectedDicomNodes,
            'dicomConnections' => $selectedDicomConnections,
            'documentation' => $selectedDocumentation,
            'dicomNodeOptions' => DicomNode::query()
                ->active()
                ->whereHas(
                    'system',
                    fn ($query) => $query->whereNull('archived_at'),
                )
                ->with('system:id,public_id,name')
                ->orderBy('name')
                ->get([
                    'id',
                    'public_id',
                    'system_id',
                    'name',
                    'ae_title',
                    'host',
                    'port',
                ]),

            'topologyNodes' => $topologyNodes,
            'topologyConnections' => $topologyConnections,

            'filters' => [
                'search' => $search,
                'type' => $type,
                'status' => $status,
                'organization' => $organizationId > 0
                    ? $organizationId
                    : null,
                'site' => $siteId > 0
                    ? $siteId
                    : null,
                'department' => $departmentId > 0
                    ? $departmentId
                    : null,
                'selected' => $selectedSystem?->public_id,
            ],

            'systemTypes' => [
                ['value' => 'pacs', 'label' => 'PACS'],
                ['value' => 'ris', 'label' => 'RIS'],
                ['value' => 'kis', 'label' => 'KIS'],
                ['value' => 'modality', 'label' => 'Modalität'],
                ['value' => 'viewer', 'label' => 'Viewer'],
                [
                    'value' => 'integration_engine',
                    'label' => 'Integrationsserver',
                ],
                ['value' => 'server', 'label' => 'Server'],
                ['value' => 'database', 'label' => 'Datenbank'],
                ['value' => 'storage', 'label' => 'Storage'],
                ['value' => 'network', 'label' => 'Netzwerkgerät'],
                ['value' => 'other', 'label' => 'Sonstiges'],
            ],

            'statuses' => [
                ['value' => 'active', 'label' => 'Aktiv'],
                ['value' => 'planned', 'label' => 'Geplant'],
                ['value' => 'maintenance', 'label' => 'Wartung'],
                ['value' => 'inactive', 'label' => 'Inaktiv'],
                ['value' => 'retired', 'label' => 'Außer Betrieb'],
            ],

            'organizations' => Organization::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name']),

            'sites' => Site::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'organization_id', 'name']),

            'departments' => Department::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'site_id', 'name']),

            'canManage' => $request
                ->user()
                ?->can('create', System::class) ?? false,
            'canManageSelected' => $selectedSystem !== null
                && ($request->user()?->can('update', $selectedSystem) ?? false),
            'canManageDicomConnections' => $request
                ->user()
                ?->can('create', DicomConnection::class) ?? false,
            'canManageDicomNodes' => $request
                ->user()
                ?->can('create', DicomNode::class) ?? false,
        ]);
    }

    public function show(Request $request, System $system, RegistryHistoryService $historyService): Response
    {
        Gate::authorize('view', $system);

        $system->load([
            'organization:id,public_id,name',
            'site:id,public_id,name',
            'department:id,public_id,name',
            'documentation' => fn ($query) => $query
                ->with('updatedByUser:id,public_id,name')
                ->orderBy('section'),
        ]);

        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $historyBase = $historyService->forContext($user, $system);
        $historyQuery = clone $historyBase;
        $historyFrom = trim((string) $request->query('history_from', ''));
        $historyTo = trim((string) $request->query('history_to', ''));
        $historyType = trim((string) $request->query('history_type', ''));
        $historyUser = trim((string) $request->query('history_user', ''));
        $historySearch = trim((string) $request->query('history_search', ''));
        $historyStatus = trim((string) $request->query('history_status', ''));

        $historyQuery
            ->when($historyFrom !== '', fn ($query) => $query->whereDate('occurred_at', '>=', $historyFrom))
            ->when($historyTo !== '', fn ($query) => $query->whereDate('occurred_at', '<=', $historyTo))
            ->when($historyType !== '', fn ($query) => $query->where('event_type', $historyType))
            ->when($historyUser !== '', fn ($query) => $query->where('metadata->actor_public_id', $historyUser))
            ->when($historyStatus !== '', fn ($query) => $query->where('metadata->status', $historyStatus))
            ->when($historySearch !== '', fn ($query) => $query->where(function ($searchQuery) use ($historySearch): void {
                $searchQuery->where('event_type', 'ilike', "%{$historySearch}%")
                    ->orWhere('subject_public_id', 'ilike', "%{$historySearch}%");
            }));

        $actorIds = (clone $historyBase)->get()->pluck('metadata')->map(
            fn (mixed $metadata): ?string => is_array($metadata) && is_string($metadata['actor_public_id'] ?? null)
                ? $metadata['actor_public_id']
                : null,
        )->filter()->unique()->values();
        $actors = User::query()->whereIn('public_id', $actorIds)->pluck('name', 'public_id');
        $now = CarbonImmutable::now();

        return Inertia::render('Registry/Systems/Show', [
            'system' => $system,
            'documentation' => $system->documentation,
            'history' => $historyQuery->paginate(15, ['*'], 'history_page')->withQueryString()->through(
                fn ($event): array => [
                    'event_id' => $event->event_id,
                    'event_type' => $event->event_type,
                    'subject_type' => class_basename((string) $event->subject_type),
                    'subject_public_id' => $event->subject_public_id,
                    'actor_name' => $actors->get($event->metadata['actor_public_id'] ?? '') ?? 'System',
                    'metadata' => $event->metadata,
                    'occurred_at' => $event->occurred_at->toIso8601String(),
                ],
            ),
            'historyStats' => [
                'total' => (clone $historyBase)->count(),
                'today' => (clone $historyBase)->where('occurred_at', '>=', $now->startOfDay())->count(),
                'last7Days' => (clone $historyBase)->where('occurred_at', '>=', $now->subDays(7))->count(),
                'last30Days' => (clone $historyBase)->where('occurred_at', '>=', $now->subDays(30))->count(),
            ],
            'historyFilters' => $request->only([
                'history_from', 'history_to', 'history_type', 'history_user', 'history_search', 'history_status',
            ]),
            'historyEventTypes' => (clone $historyBase)->reorder('event_type')->distinct()->pluck('event_type'),
            'historyUsers' => $actors->map(fn (string $name, string $publicId): array => [
                'public_id' => $publicId,
                'name' => $name,
            ])->values(),

            'systemTypes' => [
                ['value' => 'pacs', 'label' => 'PACS'],
                ['value' => 'ris', 'label' => 'RIS'],
                ['value' => 'kis', 'label' => 'KIS'],
                ['value' => 'modality', 'label' => 'Modalität'],
                ['value' => 'viewer', 'label' => 'Viewer'],
                ['value' => 'integration_engine', 'label' => 'Integrationsserver'],
                ['value' => 'server', 'label' => 'Server'],
                ['value' => 'database', 'label' => 'Datenbank'],
                ['value' => 'storage', 'label' => 'Storage'],
                ['value' => 'network', 'label' => 'Netzwerkgerät'],
                ['value' => 'other', 'label' => 'Sonstiges'],
            ],

            'statuses' => [
                ['value' => 'active', 'label' => 'Aktiv'],
                ['value' => 'planned', 'label' => 'Geplant'],
                ['value' => 'maintenance', 'label' => 'Wartung'],
                ['value' => 'inactive', 'label' => 'Inaktiv'],
                ['value' => 'retired', 'label' => 'Außer Betrieb'],
            ],

            'organizations' => Organization::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'name']),

            'sites' => Site::query()
                ->active()
                ->orderBy('name')
                ->get([
                    'id',
                    'organization_id',
                    'name',
                ]),

            'departments' => Department::query()
                ->active()
                ->orderBy('name')
                ->get([
                    'id',
                    'site_id',
                    'name',
                ]),

            'dicomNodes' => $system
                ->dicomNodes()
                ->active()
                ->with([
                    'verifications' => fn ($query) => $query
                        ->with('triggeredByUser:id,name')
                        ->latest('verified_at')
                        ->limit(20),
                ])
                ->orderBy('name')
                ->get(),

            'dicomConnections' => DicomConnection::query()
                ->active()
                ->where(function ($query) use ($system): void {
                    $query
                        ->whereHas(
                            'sourceNode',
                            function ($nodeQuery) use ($system): void {
                                $nodeQuery->where(
                                    'system_id',
                                    $system->id,
                                );
                            },
                        )
                        ->orWhereHas(
                            'targetNode',
                            function ($nodeQuery) use ($system): void {
                                $nodeQuery->where(
                                    'system_id',
                                    $system->id,
                                );
                            },
                        );
                })
                ->with([
                    'sourceNode:id,public_id,system_id,name,ae_title,host,port',
                    'sourceNode.system:id,public_id,name',
                    'targetNode:id,public_id,system_id,name,ae_title,host,port',
                    'targetNode.system:id,public_id,name',
                    'destinationNode:id,public_id,system_id,name,ae_title,host,port',
                    'destinationNode.system:id,public_id,name',
                ])
                ->orderBy('name')
                ->get(),

            'dicomNodeOptions' => DicomNode::query()
                ->active()
                ->whereHas(
                    'system',
                    function ($query): void {
                        $query->whereNull('archived_at');
                    },
                )
                ->with('system:id,public_id,name')
                ->orderBy('name')
                ->get([
                    'id',
                    'public_id',
                    'system_id',
                    'name',
                    'ae_title',
                    'host',
                    'port',
                ]),

            'canManageDicomConnections' => $request
                ->user()
                ?->can('create', DicomConnection::class) ?? false,

            'canManageDicomNodes' => $request
                ->user()
                ?->can('create', DicomNode::class) ?? false,

            'canManage' => $request->user()?->can('update', $system) ?? false,
        ]);
    }

    public function store(
        StoreSystemRequest $request,
        RegistryAudit $audit,
    ): RedirectResponse {
        $system = DB::transaction(
            function () use ($request, $audit): System {
                $system = System::query()->create($request->validated());

                $audit->record(
                    'registry.system.created',
                    $system,
                    $request->user(),
                );

                return $system;
            },
        );

        return back()->with(
            'success',
            "System {$system->name} wurde angelegt.",
        );
    }

    public function update(
        UpdateSystemRequest $request,
        System $system,
        RegistryAudit $audit,
    ): RedirectResponse {
        DB::transaction(
            function () use ($request, $system, $audit): void {
                $validated = $request->validated();
                $before = $system->only(array_keys($validated));

                $system->update($validated);

                $audit->record(
                    'registry.system.updated',
                    $system,
                    $request->user(),
                    [
                        'before' => $before,
                        'after' => $system->only(array_keys($validated)),
                    ],
                );
            },
        );

        return back()->with('success', 'System wurde aktualisiert.');
    }

    public function archive(
        Request $request,
        System $system,
        RegistryAudit $audit,
    ): RedirectResponse {
        Gate::authorize('archive', $system);

        if ($system->archived_at !== null) {
            return back()->with('error', 'System ist bereits archiviert.');
        }

        DB::transaction(
            function () use ($request, $system, $audit): void {
                $system->update([
                    'status' => 'retired',
                    'archived_at' => now(),
                ]);

                $audit->record(
                    'registry.system.archived',
                    $system,
                    $request->user(),
                );
            },
        );

        return back()->with('success', 'System wurde archiviert.');
    }
}
