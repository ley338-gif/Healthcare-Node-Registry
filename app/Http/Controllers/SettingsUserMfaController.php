<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Identity\MultiFactorAuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

final class SettingsUserMfaController extends Controller
{
    public function destroy(Request $request, User $user, MultiFactorAuthenticationService $mfa): RedirectResponse
    {
        Gate::authorize('update', $user);
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        $mfa->reset($user, $actor);

        if ($actor->is($user)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('success', 'MFA wurde zurückgesetzt. Bitte melden Sie sich erneut an.');
        }

        return back()->with('success', 'MFA wurde zurückgesetzt und bestehende Sitzungen wurden beendet.');
    }
}
