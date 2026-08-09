<?php

namespace App\Http\Controllers;

use App\Http\Requests\ResetManagedUserPasswordRequest;
use App\Http\Requests\StoreManagedUserRequest;
use App\Http\Requests\UpdateManagedUserRequest;
use App\Models\User;
use App\Services\Identity\UserAdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

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
        $beforeRoleIds = $user->roles()->pluck('roles.id')->map(static fn (mixed $id): int => (int) $id)->sort()->values()->all();
        $users->update($user, $data, $roleIds, $actor);

        if ($actor->is($user) && $roleIds !== null && $beforeRoleIds !== collect($roleIds)->sort()->values()->all()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('success', 'Die eigenen Rollen wurden geändert. Bitte melden Sie sich erneut an.');
        }

        return back()->with('success', 'Benutzer wurde aktualisiert.');
    }

    public function resetPassword(ResetManagedUserPasswordRequest $request, User $user, UserAdministrationService $users): RedirectResponse
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        $users->resetPassword($user, (string) $request->validated('password'), $actor);

        if ($actor->is($user)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('success', 'Passwort wurde gesetzt. Bitte melden Sie sich erneut an.');
        }

        return back()->with('success', 'Passwort wurde gesetzt und bestehende Sitzungen wurden beendet.');
    }
}
