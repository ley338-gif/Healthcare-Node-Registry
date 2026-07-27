<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSystemRequest;
use App\Http\Requests\UpdateSystemRequest;
use App\Models\Department;
use App\Models\DicomNode;
use App\Models\Organization;
use App\Models\Site;
use App\Models\System;
use App\Support\RegistryAudit;
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

        $systems = System::query()
            ->with([
                'organization:id,public_id,name',
                'site:id,public_id,name',
                'department:id,public_id,name',
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
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Registry/Systems/Index', [
            'items' => $systems,
            'filters' => [
                'search' => $search,
                'type' => $type,
                'status' => $status,
            ],
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
                ->get(['id', 'organization_id', 'name']),

            'departments' => Department::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'site_id', 'name']),

            'canManage' => $request->user()?->can('create', System::class) ?? false,
        ]);
    }

    public function show(Request $request, System $system): Response
    {
        Gate::authorize('view', $system);

        $system->load([
            'organization:id,public_id,name',
            'site:id,public_id,name',
            'department:id,public_id,name',
        ]);

        return Inertia::render('Registry/Systems/Show', [
            'system' => $system,

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
                ->orderBy('name')
                ->get(),

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
