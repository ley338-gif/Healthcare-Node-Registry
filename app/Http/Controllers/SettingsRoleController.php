<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreManagedRoleRequest;
use App\Http\Requests\UpdateManagedRoleRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Identity\RoleAdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $permissionIds = array_map('intval', $data['permission_ids']);
        $beforePermissionIds = $role->permissions()->pluck('permissions.id')->map(static fn (mixed $id): int => (int) $id)->sort()->values()->all();
        $actorAffected = $role->users()->whereKey($actor->id)->exists();
        $roles->update($role, $data, $permissionIds, $actor);

        if ($actorAffected && $beforePermissionIds !== collect($permissionIds)->sort()->values()->all()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('success', 'Die Berechtigungen Ihrer Rolle wurden geändert. Bitte melden Sie sich erneut an.');
        }

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
