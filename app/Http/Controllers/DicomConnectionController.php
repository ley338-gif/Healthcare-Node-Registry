<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDicomConnectionRequest;
use App\Http\Requests\UpdateDicomConnectionRequest;
use App\Models\DicomConnection;
use App\Models\DicomNode;
use App\Models\System;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

final class DicomConnectionController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', DicomConnection::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:160'],
            'source_system' => ['nullable', 'uuid'],
            'target_system' => ['nullable', 'uuid'],
            'service' => ['nullable', Rule::in(DicomConnection::SERVICES)],
            'site' => ['nullable', 'uuid'],
            'department' => ['nullable', 'uuid'],
            'status' => ['nullable', Rule::in(['active', 'planned', 'maintenance', 'inactive', 'archived'])],
            'port' => ['nullable', 'integer', 'between:1,65535'],
            'ae_title' => ['nullable', 'string', 'max:16'],
            'sort' => ['nullable', Rule::in(['name', 'source_system', 'target_system', 'service', 'port', 'status', 'last_test'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $search = trim((string) ($filters['search'] ?? ''));
        $aeTitle = trim((string) ($filters['ae_title'] ?? ''));
        $sort = (string) ($filters['sort'] ?? 'name');
        $direction = (string) ($filters['direction'] ?? 'asc');

        $query = DicomConnection::query()
            ->with([
                'sourceNode.system.organization:id,public_id,name',
                'sourceNode.system.site:id,public_id,name',
                'sourceNode.system.department:id,public_id,name',
                'targetNode.system.organization:id,public_id,name',
                'targetNode.system.site:id,public_id,name',
                'targetNode.system.department:id,public_id,name',
                'destinationNode.system:id,public_id,name',
            ])
            ->when(($filters['status'] ?? null) !== 'archived', fn ($q) => $q->whereNull('archived_at'))
            ->when(($filters['status'] ?? null) === 'archived', fn ($q) => $q->whereNotNull('archived_at'))
            ->when(isset($filters['status']) && $filters['status'] !== 'archived', fn ($q) => $q->where('status', $filters['status']))
            ->when($search !== '', function ($q) use ($search): void {
                $like = "%{$search}%";
                $q->where(function ($nested) use ($like): void {
                    $nested->where('name', 'like', $like)
                        ->orWhere('calling_ae_title', 'like', $like)
                        ->orWhere('called_ae_title', 'like', $like)
                        ->orWhere('port_override', 'like', $like)
                        ->orWhereHas('sourceNode', fn ($node) => $node->where('name', 'like', $like)->orWhere('ae_title', 'like', $like)->orWhere('host', 'like', $like)->orWhereHas('system', fn ($system) => $system->where('name', 'like', $like)))
                        ->orWhereHas('targetNode', fn ($node) => $node->where('name', 'like', $like)->orWhere('ae_title', 'like', $like)->orWhere('host', 'like', $like)->orWhere('port', 'like', $like)->orWhereHas('system', fn ($system) => $system->where('name', 'like', $like)));
                });
            })
            ->when(isset($filters['source_system']), fn ($q) => $q->whereHas('sourceNode.system', fn ($system) => $system->where('public_id', $filters['source_system'])))
            ->when(isset($filters['target_system']), fn ($q) => $q->whereHas('targetNode.system', fn ($system) => $system->where('public_id', $filters['target_system'])))
            ->when(isset($filters['service']), fn ($q) => $q->where('service', $filters['service']))
            ->when(isset($filters['site']), fn ($q) => $q->where(fn ($nested) => $nested->whereHas('sourceNode.system.site', fn ($site) => $site->where('public_id', $filters['site']))->orWhereHas('targetNode.system.site', fn ($site) => $site->where('public_id', $filters['site']))))
            ->when(isset($filters['department']), fn ($q) => $q->where(fn ($nested) => $nested->whereHas('sourceNode.system.department', fn ($department) => $department->where('public_id', $filters['department']))->orWhereHas('targetNode.system.department', fn ($department) => $department->where('public_id', $filters['department']))))
            ->when(isset($filters['port']), fn ($q) => $q->where(fn ($nested) => $nested->where('port_override', $filters['port'])->orWhere(fn ($fallback) => $fallback->whereNull('port_override')->whereHas('targetNode', fn ($node) => $node->where('port', $filters['port'])))))
            ->when($aeTitle !== '', fn ($q) => $q->where(fn ($nested) => $nested->where('calling_ae_title', 'like', "%{$aeTitle}%")->orWhere('called_ae_title', 'like', "%{$aeTitle}%")->orWhereHas('sourceNode', fn ($node) => $node->where('ae_title', 'like', "%{$aeTitle}%"))->orWhereHas('targetNode', fn ($node) => $node->where('ae_title', 'like', "%{$aeTitle}%"))));

        match ($sort) {
            'source_system' => $query->orderBy(System::select('name')->join('dicom_nodes', 'systems.id', '=', 'dicom_nodes.system_id')->whereColumn('dicom_nodes.id', 'dicom_connections.source_dicom_node_id')->limit(1), $direction),
            'target_system' => $query->orderBy(System::select('name')->join('dicom_nodes', 'systems.id', '=', 'dicom_nodes.system_id')->whereColumn('dicom_nodes.id', 'dicom_connections.target_dicom_node_id')->limit(1), $direction),
            'port' => $query->orderByRaw('COALESCE(port_override, (SELECT port FROM dicom_nodes WHERE dicom_nodes.id = target_dicom_node_id)) '.$direction),
            'last_test' => $query->orderBy(DicomNode::select('last_verified_at')->whereColumn('dicom_nodes.id', 'dicom_connections.target_dicom_node_id'), $direction),
            default => $query->orderBy($sort, $direction),
        };

        $nodes = DicomNode::query()->active()->whereHas('system', fn ($q) => $q->whereNull('archived_at'))->with('system:id,public_id,name')->orderBy('name')->get(['id', 'public_id', 'system_id', 'name', 'ae_title', 'host', 'port']);
        $systems = System::query()->active()->with(['site:id,public_id,name', 'department:id,public_id,name'])->orderBy('name')->get(['id', 'public_id', 'site_id', 'department_id', 'name']);

        return Inertia::render('Connections/Index', [
            'connections' => $query->paginate(20)->withQueryString(),
            'nodes' => $nodes,
            'systems' => $systems,
            'filters' => $filters,
            'services' => DicomConnection::SERVICES,
            'canManage' => $request->user()?->can('create', DicomConnection::class) ?? false,
        ]);
    }

    public function store(
        StoreDicomConnectionRequest $request,
        RegistryAudit $audit,
    ): RedirectResponse {
        $connection = DB::transaction(
            function () use ($request, $audit): DicomConnection {
                $connection = DicomConnection::query()->create(
                    $request->validated(),
                );

                $audit->record(
                    'registry.dicom_connection.created',
                    $connection,
                    $request->user(),
                    [
                        'source_dicom_node_id' => $connection->source_dicom_node_id,
                        'target_dicom_node_id' => $connection->target_dicom_node_id,
                        'destination_dicom_node_id' => $connection->destination_dicom_node_id,
                        'service' => $connection->service,
                    ],
                );

                return $connection;
            },
        );

        return back()->with(
            'success',
            "DICOM-Verbindung {$connection->name} wurde angelegt.",
        );
    }

    public function update(
        UpdateDicomConnectionRequest $request,
        DicomConnection $dicomConnection,
        RegistryAudit $audit,
    ): RedirectResponse {
        DB::transaction(
            function () use (
                $request,
                $dicomConnection,
                $audit,
            ): void {
                $validated = $request->validated();

                $before = $dicomConnection->only(
                    array_keys($validated),
                );

                $dicomConnection->update($validated);

                $audit->record(
                    'registry.dicom_connection.updated',
                    $dicomConnection,
                    $request->user(),
                    [
                        'before' => $before,
                        'after' => $dicomConnection->only(
                            array_keys($validated),
                        ),
                    ],
                );
            },
        );

        return back()->with(
            'success',
            'DICOM-Verbindung wurde aktualisiert.',
        );
    }

    public function archive(
        Request $request,
        DicomConnection $dicomConnection,
        RegistryAudit $audit,
    ): RedirectResponse {
        Gate::authorize('archive', $dicomConnection);

        if ($dicomConnection->archived_at !== null) {
            return back()->with(
                'error',
                'DICOM-Verbindung ist bereits archiviert.',
            );
        }

        DB::transaction(
            function () use (
                $request,
                $dicomConnection,
                $audit,
            ): void {
                $dicomConnection->update([
                    'status' => 'inactive',
                    'test_enabled' => false,
                    'archived_at' => now(),
                ]);

                $audit->record(
                    'registry.dicom_connection.archived',
                    $dicomConnection,
                    $request->user(),
                    [
                        'source_dicom_node_id' => $dicomConnection->source_dicom_node_id,
                        'target_dicom_node_id' => $dicomConnection->target_dicom_node_id,
                        'service' => $dicomConnection->service,
                    ],
                );
            },
        );

        return back()->with(
            'success',
            'DICOM-Verbindung wurde archiviert.',
        );
    }

    public function duplicate(Request $request, DicomConnection $dicomConnection, RegistryAudit $audit): RedirectResponse
    {
        Gate::authorize('create', DicomConnection::class);

        $copy = DB::transaction(function () use ($request, $dicomConnection, $audit): DicomConnection {
            $copy = $dicomConnection->replicate();
            $copy->public_id = (string) \Illuminate\Support\Str::uuid7();
            $copy->name = $dicomConnection->name.' (Kopie)';
            $copy->service = collect(DicomConnection::SERVICES)->first(fn (string $service) => ! DicomConnection::query()->where('source_dicom_node_id', $copy->source_dicom_node_id)->where('target_dicom_node_id', $copy->target_dicom_node_id)->where('service', $service)->exists());
            abort_if($copy->service === null, 422, 'Für diesen Pfad sind bereits alle Dienste dokumentiert.');
            if ($copy->service !== DicomConnection::SERVICE_MOVE) {
                $copy->destination_dicom_node_id = null;
            }
            $copy->archived_at = null;
            $copy->save();
            $audit->record('registry.dicom_connection.duplicated', $copy, $request->user(), ['source_connection_public_id' => $dicomConnection->public_id]);

            return $copy;
        });

        return back()->with('success', "DICOM-Verbindung {$copy->name} wurde dupliziert.");
    }
}
