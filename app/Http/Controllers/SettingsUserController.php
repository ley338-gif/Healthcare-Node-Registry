<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetManagedUserPasswordRequest;
use App\Http\Requests\StoreManagedUserRequest;
use App\Http\Requests\UpdateManagedUserRequest;
use App\Models\User;
use App\Services\Identity\UserAdministrationService;
use Illuminate\Http\RedirectResponse;

final class SettingsUserController extends Controller
{
    public function store(StoreManagedUserRequest $request, UserAdministrationService $users): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        $data = $request->validated();
        $roleIds = array_map('intval', $data['role_ids'] ?? []);
        abort_if($roleIds !== [] && ! $actor->hasPermission('roles.manage'), 403);
        $users->create($data, $roleIds, $actor);

        return back()->with('success', 'Benutzer wurde angelegt.');
    }

    public function update(UpdateManagedUserRequest $request, User $user, UserAdministrationService $users): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        $data = $request->validated();
        $roleIds = $actor->hasPermission('roles.manage') ? array_map('intval', $data['role_ids'] ?? []) : null;
        $users->update($user, $data, $roleIds, $actor);

        return back()->with('success', 'Benutzer wurde aktualisiert.');
    }

    public function resetPassword(ResetManagedUserPasswordRequest $request, User $user, UserAdministrationService $users): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        $users->resetPassword($user, (string) $request->validated('password'), $actor);

        return back()->with('success', 'Passwort wurde gesetzt und bestehende Sitzungen wurden beendet.');
    }
}
