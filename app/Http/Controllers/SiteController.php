<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteRequest;
use App\Http\Requests\UpdateSiteRequest;
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

final class SiteController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Site::class);

        $search = trim((string) $request->query('search', ''));
        $archived = $request->boolean('archived');

        $items = Site::query()
            ->with('organization:id,public_id,name')
            ->withCount([
                'departments as departments_count' => fn ($query) => $query->whereNull('archived_at'),
            ])
            ->when(! $archived, fn ($query) => $query->whereNull('archived_at'))
            ->when(
                $search !== '',
                fn ($query) => $query->where(
                    fn ($searchQuery) => $searchQuery
                        ->where('name', 'ilike', "%{$search}%")
                        ->orWhere('city', 'ilike', "%{$search}%")
                        ->orWhere('code', 'ilike', "%{$search}%"),
                ),
            )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $visibleSiteIds = $items
            ->getCollection()
            ->pluck('id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $systemCounts = System::query()
            ->whereNull('archived_at')
            ->whereIn('site_id', $visibleSiteIds)
            ->selectRaw('site_id, count(*) as aggregate')
            ->groupBy('site_id')
            ->pluck('aggregate', 'site_id');

        $items->setCollection(
            $items->getCollection()->map(function (Site $site) use ($systemCounts): Site {
                $site->setAttribute(
                    'systems_count',
                    (int) ($systemCounts[$site->id] ?? 0),
                );

                return $site;
            }),
        );

        $requestedPublicId = trim((string) $request->query('selected', ''));
        $selectedSite = null;

        if ($requestedPublicId !== '') {
            $selectedSite = Site::query()
                ->where('public_id', $requestedPublicId)
                ->when(! $archived, fn ($query) => $query->whereNull('archived_at'))
                ->first();
        }

        if ($selectedSite === null) {
            $selectedSite = $items->getCollection()->first();
        }

        $selected = null;

        if ($selectedSite instanceof Site) {
            $selectedSite->load('organization:id,public_id,name');

            $departments = Department::query()
                ->where('site_id', $selectedSite->id)
                ->whereNull('archived_at')
                ->orderBy('name')
                ->get([
                    'id',
                    'public_id',
                    'site_id',
                    'name',
                    'code',
                    'specialty',
                    'description',
                ]);

            $systems = System::query()
                ->where('site_id', $selectedSite->id)
                ->whereNull('archived_at')
                ->with('department:id,public_id,name')
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
                'id' => $selectedSite->id,
                'public_id' => $selectedSite->public_id,
                'organization_id' => $selectedSite->organization_id,
                'organization' => $selectedSite->organization,
                'name' => $selectedSite->name,
                'code' => $selectedSite->code,
                'street' => $selectedSite->street,
                'postal_code' => $selectedSite->postal_code,
                'city' => $selectedSite->city,
                'country_code' => $selectedSite->country_code,
                'timezone' => $selectedSite->timezone,
                'description' => $selectedSite->description,
                'archived_at' => $selectedSite->archived_at,
                'created_at' => $selectedSite->created_at->toIso8601String(),
                'updated_at' => $selectedSite->updated_at->toIso8601String(),
                'departments_count' => $departments->count(),
                'systems_count' => $systems->count(),
                'dicom_nodes_count' => $systems->sum('dicom_nodes_count'),
                'failed_dicom_nodes_count' => $systems->sum('failed_dicom_nodes_count'),
                'departments' => $departments,
                'systems' => $systems,
            ];
        }

        return Inertia::render('Registry/Sites', [
            'items' => $items,
            'selected' => $selected,
            'organizations' => Organization::query()
                ->active()
                ->orderBy('name')
                ->get(['id', 'public_id', 'name']),
            'filters' => [
                'search' => $search,
                'archived' => $archived,
            ],
            'canManage' => $request->user()?->can('create', Site::class) ?? false,
            'canUpdateSelected' => $selectedSite !== null
                ? ($request->user()?->can('update', $selectedSite) ?? false)
                : false,
            'canArchiveSelected' => $selectedSite !== null
                ? ($request->user()?->can('archive', $selectedSite) ?? false)
                : false,
        ]);
    }

    public function store(StoreSiteRequest $request, RegistryAudit $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $audit): void {
            $site = Site::query()->create($request->validated());
            $audit->record('registry.site.created', $site, $request->user());
        });

        return back()->with('success', 'Standort wurde angelegt.');
    }

    public function update(
        UpdateSiteRequest $request,
        Site $site,
        RegistryAudit $audit,
    ): RedirectResponse {
        DB::transaction(function () use ($request, $site, $audit): void {
            $validated = $request->validated();
            $before = $site->only(array_keys($validated));

            $site->update($validated);

            $audit->record(
                'registry.site.updated',
                $site,
                $request->user(),
                [
                    'before' => $before,
                    'after' => $site->only(array_keys($validated)),
                ],
            );
        });

        return back()->with('success', 'Standort wurde aktualisiert.');
    }

    public function archive(
        Request $request,
        Site $site,
        RegistryAudit $audit,
    ): RedirectResponse {
        Gate::authorize('archive', $site);

        if ($site->departments()->whereNull('archived_at')->exists()) {
            return back()->with('error', 'Zuerst alle Abteilungen archivieren.');
        }

        DB::transaction(function () use ($request, $site, $audit): void {
            $site->update(['archived_at' => now()]);
            $audit->record('registry.site.archived', $site, $request->user());
        });

        return back()->with('success', 'Standort wurde archiviert.');
    }
}
