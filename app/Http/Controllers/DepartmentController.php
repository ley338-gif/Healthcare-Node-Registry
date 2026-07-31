<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use App\Models\Site;
use App\Models\System;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class DepartmentController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Department::class);

        $search = trim((string) $request->query('search', ''));
        $archived = $request->boolean('archived');

        $items = Department::query()
            ->with('site.organization:id,name')
            ->when(! $archived, fn ($query) => $query->whereNull('archived_at'))
            ->when(
                $search !== '',
                fn ($query) => $query->where(
                    fn ($searchQuery) => $searchQuery
                        ->where('name', 'ilike', "%{$search}%")
                        ->orWhere('code', 'ilike', "%{$search}%")
                        ->orWhere('specialty', 'ilike', "%{$search}%"),
                ),
            )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $visibleDepartmentIds = $items
            ->getCollection()
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $systemCounts = System::query()
            ->whereNull('archived_at')
            ->whereIn('department_id', $visibleDepartmentIds)
            ->selectRaw('department_id, count(*) as aggregate')
            ->groupBy('department_id')
            ->pluck('aggregate', 'department_id');

        $items->setCollection(
            $items->getCollection()->map(function (Department $department) use ($systemCounts): Department {
                $department->setAttribute(
                    'systems_count',
                    (int) ($systemCounts[$department->id] ?? 0),
                );

                return $department;
            }),
        );

        $requestedPublicId = trim((string) $request->query('selected', ''));
        $selectedDepartment = null;

        if ($requestedPublicId !== '') {
            $selectedDepartment = Department::query()
                ->where('public_id', $requestedPublicId)
                ->when(! $archived, fn ($query) => $query->whereNull('archived_at'))
                ->first();
        }

        if ($selectedDepartment === null) {
            $selectedDepartment = $items->getCollection()->first();
        }

        $selected = null;

        if ($selectedDepartment instanceof Department) {
            $selectedDepartment->load('site.organization:id,public_id,name');

            $systems = System::query()
                ->where('department_id', $selectedDepartment->id)
                ->whereNull('archived_at')
                ->withCount([
                    'dicomNodes as dicom_nodes_count' => fn ($query) => $query->active(),
                    'dicomNodes as failed_dicom_nodes_count' => fn ($query) => $query
                        ->active()
                        ->whereNotNull('last_verification_status')
                        ->where('last_verification_status', '!=', 'success'),
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
                ]);

            $selected = [
                'id' => $selectedDepartment->id,
                'public_id' => $selectedDepartment->public_id,
                'site_id' => $selectedDepartment->site_id,
                'site' => $selectedDepartment->site,
                'name' => $selectedDepartment->name,
                'code' => $selectedDepartment->code,
                'specialty' => $selectedDepartment->specialty,
                'description' => $selectedDepartment->description,
                'archived_at' => $selectedDepartment->archived_at,
                'created_at' => $selectedDepartment->created_at->toIso8601String(),
                'updated_at' => $selectedDepartment->updated_at->toIso8601String(),
                'systems_count' => $systems->count(),
                'dicom_nodes_count' => $systems->sum('dicom_nodes_count'),
                'failed_dicom_nodes_count' => $systems->sum('failed_dicom_nodes_count'),
                'systems' => $systems,
            ];
        }

        return Inertia::render('Registry/Departments', [
            'items' => $items,
            'selected' => $selected,
            'sites' => Site::query()
                ->active()
                ->with('organization:id,name')
                ->orderBy('name')
                ->get(['id', 'public_id', 'organization_id', 'name']),
            'filters' => [
                'search' => $search,
                'archived' => $archived,
            ],
            'canManage' => $request->user()?->can('create', Department::class) ?? false,
            'canUpdateSelected' => $selectedDepartment !== null
                ? ($request->user()?->can('update', $selectedDepartment) ?? false)
                : false,
            'canArchiveSelected' => $selectedDepartment !== null
                ? ($request->user()?->can('archive', $selectedDepartment) ?? false)
                : false,
        ]);
    }

    public function store(
        StoreDepartmentRequest $request,
        RegistryAudit $audit,
    ): RedirectResponse {
        DB::transaction(function () use ($request, $audit): void {
            $department = Department::query()->create($request->validated());
            $audit->record('registry.department.created', $department, $request->user());
        });

        return back()->with('success', 'Abteilung wurde angelegt.');
    }

    public function update(
        UpdateDepartmentRequest $request,
        Department $department,
        RegistryAudit $audit,
    ): RedirectResponse {
        DB::transaction(function () use ($request, $department, $audit): void {
            $validated = $request->validated();
            $before = $department->only(array_keys($validated));

            $department->update($validated);

            $audit->record(
                'registry.department.updated',
                $department,
                $request->user(),
                [
                    'before' => $before,
                    'after' => $department->only(array_keys($validated)),
                ],
            );
        });

        return back()->with('success', 'Abteilung wurde aktualisiert.');
    }

    public function archive(
        Request $request,
        Department $department,
        RegistryAudit $audit,
    ): RedirectResponse {
        Gate::authorize('archive', $department);

        DB::transaction(function () use ($request, $department, $audit): void {
            $department->update(['archived_at' => now()]);
            $audit->record('registry.department.archived', $department, $request->user());
        });

        return back()->with('success', 'Abteilung wurde archiviert.');
    }
}
