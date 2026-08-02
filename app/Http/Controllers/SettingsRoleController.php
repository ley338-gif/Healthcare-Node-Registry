<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreManagedRoleRequest;
use App\Http\Requests\UpdateManagedRoleRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Identity\RoleAdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class SettingsRoleController extends Controller
{
    public function store(StoreManagedRoleRequest $request, RoleAdministrationService $roles): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        $data = $request->validated();
        $roles->create($data, array_map('intval', $data['permission_ids']), $actor);

        return back()->with('success', 'Rolle wurde angelegt.');
    }

    public function update(UpdateManagedRoleRequest $request, Role $role, RoleAdministrationService $roles): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        $data = $request->validated();
        $roles->update($role, $data, array_map('intval', $data['permission_ids']), $actor);

        return back()->with('success', 'Rolle wurde aktualisiert.');
    }

    public function destroy(Request $request, Role $role, RoleAdministrationService $roles): RedirectResponse
    {
        Gate::authorize('delete', $role);
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        $roles->delete($role, $actor);

        return back()->with('success', 'Rolle wurde gelöscht.');
    }
}
