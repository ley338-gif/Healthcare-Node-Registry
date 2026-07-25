<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Models\Organization;
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
        $items = Organization::query()->withCount(['sites' => fn ($q) => $q->whereNull('archived_at')])->when(! $archived, fn ($q) => $q->whereNull('archived_at'))->when($search !== '', fn ($q) => $q->where(fn ($x) => $x->where('name', 'ilike', "%{$search}%")->orWhere('short_name', 'ilike', "%{$search}%")))->orderBy('name')->paginate(20)->withQueryString();

        return Inertia::render('Registry/Organizations', ['items' => $items, 'filters' => ['search' => $search, 'archived' => $archived], 'canManage' => $request->user()?->can('create', Organization::class) ?? false]);
    }

    public function store(StoreOrganizationRequest $request, RegistryAudit $audit): RedirectResponse
    {
        $model = DB::transaction(function () use ($request, $audit) {
            $m = Organization::query()->create($request->validated());
            $audit->record('registry.organization.created', $m, $request->user());

            return $m;
        });

        return back()->with('success', "Organisation {$model->name} wurde angelegt.");
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization, RegistryAudit $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $organization, $audit) {
            $before = $organization->only(['name', 'short_name', 'description']);
            $organization->update($request->validated());
            $audit->record('registry.organization.updated', $organization, $request->user(), ['before' => $before, 'after' => $organization->only(array_keys($before))]);
        });

        return back()->with('success', 'Organisation wurde aktualisiert.');
    }

    public function archive(Request $request, Organization $organization, RegistryAudit $audit): RedirectResponse
    {
        Gate::authorize('archive', $organization);
        if ($organization->sites()->whereNull('archived_at')->exists()) {
            return back()->with('error', 'Zuerst alle Standorte archivieren.');
        } DB::transaction(function () use ($request, $organization, $audit) {
            $organization->update(['archived_at' => now()]);
            $audit->record('registry.organization.archived', $organization, $request->user());
        });

        return back()->with('success', 'Organisation wurde archiviert.');
    }
}
