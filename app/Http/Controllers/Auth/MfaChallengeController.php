<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MfaCodeRequest;
use App\Models\User;
use App\Services\Identity\MultiFactorAuthenticationService;
use App\Support\MfaFactor;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class MfaChallengeController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if (! $this->pendingUser($request) instanceof User) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/MfaChallenge');
    }

    public function store(
        MfaCodeRequest $request,
        MultiFactorAuthenticationService $mfa,
        RegistryAudit $audit,
    ): RedirectResponse {
        $user = $this->pendingUser($request);
        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        $rateKey = "mfa|{$user->public_id}|{$request->ip()}";
        if (RateLimiter::tooManyAttempts($rateKey, (int) config('mfa.max_attempts', 5))) {
            throw ValidationException::withMessages(['code' => 'Zu viele MFA-Versuche. Bitte später erneut anmelden.']);
        }

        $factor = $mfa->verifyAndConsume($user, (string) $request->validated('code'));
        if (! $factor instanceof MfaFactor) {
            RateLimiter::hit($rateKey, 60);
            throw ValidationException::withMessages(['code' => 'Der MFA- oder Wiederherstellungscode ist ungültig.']);
        }

        RateLimiter::clear($rateKey);
        $remember = (bool) $request->session()->pull('auth.mfa_remember', false);
        $request->session()->forget(['auth.mfa_pending_user_id', 'auth.mfa_pending_at']);
        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();
        $request->session()->put([
            'auth.mfa_verified_user_id' => $user->getAuthIdentifier(),
            'auth.mfa_verified_at' => now()->timestamp,
        ]);
        $audit->record('auth.mfa_verified', $user, $user, [
            'factor' => $factor->value,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        $audit->record('auth.login', $user, $user, [
            'mfa_factor' => $factor->value,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($factor === MfaFactor::RecoveryCode) {
            return redirect()->intended(route('dashboard', absolute: false))->with(
                'success',
                'Wiederherstellungscode verwendet. Verbleibende Codes: '.$mfa->remainingRecoveryCodes($user->fresh()),
            );
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function pendingUser(Request $request): ?User
    {
        $pendingAt = (int) $request->session()->get('auth.mfa_pending_at', 0);
        if ($pendingAt === 0 || now()->timestamp - $pendingAt > (int) config('mfa.challenge_ttl_seconds', 300)) {
            $request->session()->forget(['auth.mfa_pending_user_id', 'auth.mfa_pending_at', 'auth.mfa_remember']);

            return null;
        }

        $user = User::query()->find($request->session()->get('auth.mfa_pending_user_id'));

        return $user instanceof User && $user->is_active && $user->two_factor_confirmed_at !== null ? $user : null;
    }
}
