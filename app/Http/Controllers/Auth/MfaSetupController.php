<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MfaCodeRequest;
use App\Models\User;
use App\Services\Identity\MultiFactorAuthenticationService;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class MfaSetupController extends Controller
{
    public function create(Request $request, MultiFactorAuthenticationService $mfa): Response|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        if ($mfa->isEnabled($user)) {
            return redirect()->route('dashboard');
        }

        $enrollment = $mfa->enrollment($user);

        return Inertia::render('Auth/MfaSetup', [
            'secret' => $enrollment['secret'],
            'provisioningUri' => $enrollment['provisioning_uri'],
            'required' => $mfa->isRequired($user),
        ]);
    }

    public function store(
        MfaCodeRequest $request,
        MultiFactorAuthenticationService $mfa,
        RegistryAudit $audit,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $recoveryCodes = $mfa->confirmEnrollment($user, (string) $request->validated('code'));
        $request->session()->put([
            'auth.mfa_verified_user_id' => $user->getAuthIdentifier(),
            'auth.mfa_verified_at' => now()->timestamp,
        ]);

        if ($request->session()->pull('auth.mfa_login_pending_audit', false)) {
            $audit->record('auth.login', $user, $user, [
                'mfa_factor' => 'enrollment',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        $request->session()->flash('auth.mfa_recovery_codes', $recoveryCodes);

        return redirect()->route('mfa.recovery-codes');
    }

    public function recoveryCodes(Request $request): Response|RedirectResponse
    {
        $codes = $request->session()->pull('auth.mfa_recovery_codes');
        if (! is_array($codes)) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/MfaRecoveryCodes', ['codes' => array_values($codes)]);
    }
}
