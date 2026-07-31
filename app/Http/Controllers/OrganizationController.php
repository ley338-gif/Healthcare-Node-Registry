<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Department;
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

final class OrganizationController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Organization::class);

        $search = trim((string) $request->query('search', ''));
        $archived = $request->boolean('archived');

        $items = Organization::query()
            ->withCount([
                'sites as sites_count' => fn ($query) => $query->whereNull('archived_at'),
            ])
            ->when(
                ! $archived,
                fn ($query) => $query->whereNull('archived_at'),
            )
            ->when(
                $search !== '',
                fn ($query) => $query->where(
                    fn ($searchQuery) => $searchQuery
                        ->where('name', 'ilike', "%{$search}%")
                        ->orWhere('short_name', 'ilike', "%{$search}%"),
                ),
            )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $visibleOrganizationIds = $items
            ->getCollection()
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $systemCounts = System::query()
            ->whereNull('archived_at')
            ->whereIn('organization_id', $visibleOrganizationIds)
            ->selectRaw('organization_id, count(*) as aggregate')
            ->groupBy('organization_id')
            ->pluck('aggregate', 'organization_id');

        $items->setCollection(
            $items->getCollection()->map(function (Organization $organization) use ($systemCounts): Organization {
                $organization->setAttribute(
                    'systems_count',
                    (int) ($systemCounts[$organization->id] ?? 0),
                );

                return $organization;
            }),
        );

        $requestedPublicId = trim((string) $request->query('selected', ''));
        $selectedOrganization = null;

        if ($requestedPublicId !== '') {
            $selectedOrganization = Organization::query()
                ->where('public_id', $requestedPublicId)
                ->when(
                    ! $archived,
                    fn ($query) => $query->whereNull('archived_at'),
                )
                ->first();
        }

        if ($selectedOrganization === null) {
            $selectedOrganization = $items->getCollection()->first();
        }

        $selected = null;

        if ($selectedOrganization instanceof Organization) {
            $sites = Site::query()
                ->where('organization_id', $selectedOrganization->id)
                ->whereNull('archived_at')
                ->withCount([
                    'departments as departments_count' => fn ($query) => $query->whereNull('archived_at'),
                ])
                ->orderBy('name')
                ->get([
                    'id',
                    'public_id',
                    'organization_id',
                    'name',
                    'code',
                    'city',
                    'country_code',
                    'description',
                ]);

            $systems = System::query()
                ->where('organization_id', $selectedOrganization->id)
                ->whereNull('archived_at')
                ->with([
                    'site:id,public_id,name',
                    'department:id,public_id,name',
                ])
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

            $departmentCount = Department::query()
                ->whereHas(
                    'site',
                    fn ($query) => $query->where(
                        'organization_id',
                        $selectedOrganization->id,
                    ),
                )
                ->whereNull('archived_at')
                ->count();

            $selected = [
                'id' => $selectedOrganization->id,
                'public_id' => $selectedOrganization->public_id,
                'name' => $selectedOrganization->name,
                'short_name' => $selectedOrganization->short_name,
                'description' => $selectedOrganization->description,
                'archived_at' => $selectedOrganization->archived_at,
                'created_at' => $selectedOrganization->created_at->toIso8601String(),
                'updated_at' => $selectedOrganization->updated_at->toIso8601String(),
                'sites_count' => $sites->count(),
                'departments_count' => $departmentCount,
                'systems_count' => $systems->count(),
                'dicom_nodes_count' => $systems->sum('dicom_nodes_count'),
                'failed_dicom_nodes_count' => $systems->sum('failed_dicom_nodes_count'),
                'sites' => $sites,
                'systems' => $systems,
            ];
        }

        return Inertia::render('Registry/Organizations', [
            'items' => $items,
            'selected' => $selected,
            'filters' => [
                'search' => $search,
                'archived' => $archived,
            ],
            'canManage' => $request
                ->user()
                ?->can('create', Organization::class) ?? false,
            'canUpdateSelected' => $selectedOrganization !== null
                ? ($request->user()?->can('update', $selectedOrganization) ?? false)
                : false,
            'canArchiveSelected' => $selectedOrganization !== null
                ? ($request->user()?->can('archive', $selectedOrganization) ?? false)
                : false,
        ]);
    }

    public function store(
        StoreOrganizationRequest $request,
        RegistryAudit $audit,
    ): RedirectResponse {
        $organization = DB::transaction(
            function () use ($request, $audit): Organization {
                $organization = Organization::query()->create($request->validated());

                $audit->record(
                    'registry.organization.created',
                    $organization,
                    $request->user(),
                );

                return $organization;
            },
        );

        return back()->with(
            'success',
            "Organisation {$organization->name} wurde angelegt.",
        );
    }

    public function update(
        UpdateOrganizationRequest $request,
        Organization $organization,
        RegistryAudit $audit,
    ): RedirectResponse {
        DB::transaction(
            function () use ($request, $organization, $audit): void {
                $validated = $request->validated();
                $before = $organization->only(array_keys($validated));

                $organization->update($validated);

                $audit->record(
                    'registry.organization.updated',
                    $organization,
                    $request->user(),
                    [
                        'before' => $before,
                        'after' => $organization->only(array_keys($validated)),
                    ],
                );
            },
        );

        return back()->with('success', 'Organisation wurde aktualisiert.');
    }

    public function archive(
        Request $request,
        Organization $organization,
        RegistryAudit $audit,
    ): RedirectResponse {
        Gate::authorize('archive', $organization);

        if ($organization->sites()->whereNull('archived_at')->exists()) {
            return back()->with('error', 'Zuerst alle Standorte archivieren.');
        }

        DB::transaction(
            function () use ($request, $organization, $audit): void {
                $organization->update(['archived_at' => now()]);

                $audit->record(
                    'registry.organization.archived',
                    $organization,
                    $request->user(),
                );
            },
        );

        return back()->with('success', 'Organisation wurde archiviert.');
    }
}
