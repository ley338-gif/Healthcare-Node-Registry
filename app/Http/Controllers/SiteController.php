<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteRequest;
use App\Http\Requests\UpdateSiteRequest;
use App\Models\Organization;
use App\Models\Site;
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
        $items = Site::query()->with('organization:id,public_id,name')->withCount(['departments' => fn ($q) => $q->whereNull('archived_at')])->when(! $archived, fn ($q) => $q->whereNull('archived_at'))->when($search !== '', fn ($q) => $q->where(fn ($x) => $x->where('name', 'ilike', "%{$search}%")->orWhere('city', 'ilike', "%{$search}%")->orWhere('code', 'ilike', "%{$search}%")))->orderBy('name')->paginate(20)->withQueryString();

        return Inertia::render('Registry/Sites', ['items' => $items, 'organizations' => Organization::query()->active()->orderBy('name')->get(['id', 'public_id', 'name']), 'filters' => ['search' => $search, 'archived' => $archived], 'canManage' => $request->user()?->can('create', Site::class) ?? false]);
    }

    public function store(StoreSiteRequest $request, RegistryAudit $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $audit) {
            $m = Site::query()->create($request->validated());
            $audit->record('registry.site.created', $m, $request->user());
        });

        return back()->with('success', 'Standort wurde angelegt.');
    }

    public function update(UpdateSiteRequest $request, Site $site, RegistryAudit $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $site, $audit) {
            $before = $site->only(['organization_id', 'name', 'code', 'street', 'postal_code', 'city', 'country_code', 'timezone', 'description']);
            $site->update($request->validated());
            $audit->record('registry.site.updated', $site, $request->user(), ['before' => $before, 'after' => $site->only(array_keys($before))]);
        });

        return back()->with('success', 'Standort wurde aktualisiert.');
    }

    public function archive(Request $request, Site $site, RegistryAudit $audit): RedirectResponse
    {
        Gate::authorize('archive', $site);
        if ($site->departments()->whereNull('archived_at')->exists()) {
            return back()->with('error', 'Zuerst alle Abteilungen archivieren.');
        }DB::transaction(function () use ($request, $site, $audit) {
            $site->update(['archived_at' => now()]);
            $audit->record('registry.site.archived', $site, $request->user());
        });

        return back()->with('success', 'Standort wurde archiviert.');
    }
}
