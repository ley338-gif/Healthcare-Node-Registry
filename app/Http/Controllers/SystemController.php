<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSystemRequest;
use App\Http\Requests\UpdateSystemRequest;
use App\Models\System;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class SystemController extends Controller
{
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
