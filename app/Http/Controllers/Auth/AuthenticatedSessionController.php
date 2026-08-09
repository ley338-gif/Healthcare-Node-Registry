<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\Identity\MultiFactorAuthenticationService;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(
        LoginRequest $request,
        RegistryAudit $audit,
        MultiFactorAuthenticationService $mfa,
    ): RedirectResponse {
        $user = $request->authenticate();

        if ($mfa->isEnabled($user)) {
            Auth::guard('web')->logout();
            $request->session()->regenerate();
            $request->session()->put([
                'auth.mfa_pending_user_id' => $user->getAuthIdentifier(),
                'auth.mfa_pending_at' => now()->timestamp,
                'auth.mfa_remember' => $request->boolean('remember'),
            ]);

            return redirect()->route('mfa.challenge');
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($mfa->isRequired($user)) {
            $request->session()->put('auth.mfa_login_pending_audit', true);
            $audit->record('auth.mfa_setup_required', $user, $user, [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return redirect()->route('mfa.setup');
        }

        $audit->record('auth.login', $user, $user, [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function destroy(Request $request, RegistryAudit $audit): RedirectResponse
    {
        $user = $request->user();
        if ($user instanceof User) {
            $audit->record('auth.logout', $user, $user, ['ip_address' => $request->ip(), 'user_agent' => $request->userAgent()]);
        }
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
