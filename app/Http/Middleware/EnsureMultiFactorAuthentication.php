<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Identity\MultiFactorAuthenticationService;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureMultiFactorAuthentication
{
    public function __construct(private readonly MultiFactorAuthenticationService $mfa) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User || (! $this->mfa->isRequired($user) && ! $this->mfa->isEnabled($user))) {
            return $next($request);
        }

        if (app()->environment('testing') && $request->session()->get('auth.mfa_test_bypass') === true) {
            return $next($request);
        }

        if (! $this->mfa->isEnabled($user)) {
            return redirect()->route('mfa.setup');
        }

        if ((int) $request->session()->get('auth.mfa_verified_user_id') === (int) $user->getAuthIdentifier()) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('error', 'Bitte melden Sie sich erneut mit Passwort und MFA an.');
    }
}
