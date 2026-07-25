<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use App\Models\Site;
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
        $items = Department::query()->with('site.organization:id,name')->when(! $archived, fn ($q) => $q->whereNull('archived_at'))->when($search !== '', fn ($q) => $q->where(fn ($x) => $x->where('name', 'ilike', "%{$search}%")->orWhere('code', 'ilike', "%{$search}%")->orWhere('specialty', 'ilike', "%{$search}%")))->orderBy('name')->paginate(20)->withQueryString();

        return Inertia::render('Registry/Departments', ['items' => $items, 'sites' => Site::query()->active()->with('organization:id,name')->orderBy('name')->get(['id', 'public_id', 'organization_id', 'name']), 'filters' => ['search' => $search, 'archived' => $archived], 'canManage' => $request->user()?->can('create', Department::class) ?? false]);
    }

    public function store(StoreDepartmentRequest $request, RegistryAudit $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $audit) {
            $m = Department::query()->create($request->validated());
            $audit->record('registry.department.created', $m, $request->user());
        });

        return back()->with('success', 'Abteilung wurde angelegt.');
    }

    public function update(UpdateDepartmentRequest $request, Department $department, RegistryAudit $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $department, $audit) {
            $before = $department->only(['site_id', 'name', 'code', 'specialty', 'description']);
            $department->update($request->validated());
            $audit->record('registry.department.updated', $department, $request->user(), ['before' => $before, 'after' => $department->only(array_keys($before))]);
        });

        return back()->with('success', 'Abteilung wurde aktualisiert.');
    }

    public function archive(Request $request, Department $department, RegistryAudit $audit): RedirectResponse
    {
        Gate::authorize('archive', $department);
        DB::transaction(function () use ($request, $department, $audit) {
            $department->update(['archived_at' => now()]);
            $audit->record('registry.department.archived', $department, $request->user());
        });

        return back()->with('success', 'Abteilung wurde archiviert.');
    }
}
