<?php

namespace App\Services\Identity;

use App\Models\User;
use App\Support\MfaFactor;
use App\Support\RegistryAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use OTPHP\InternalClock;
use OTPHP\TOTP;

final class MultiFactorAuthenticationService
{
    private const RECOVERY_CODE_COUNT = 8;

    public function __construct(private readonly RegistryAudit $audit) {}

    public function isRequired(User $user): bool
    {
        if ($user->hasRole('system-administrator')) {
            return true;
        }

        /** @var list<string> $permissions */
        $permissions = config('mfa.required_permissions', []);

        return $permissions !== [] && $user->roles()
            ->whereHas('permissions', fn ($query) => $query->whereIn('name', $permissions))
            ->exists();
    }

    public function isEnabled(User $user): bool
    {
        return filled($user->two_factor_secret) && $user->two_factor_confirmed_at !== null;
    }

    /** @return array{secret: string, provisioning_uri: string} */
    public function enrollment(User $user): array
    {
        if ($this->isEnabled($user)) {
            throw ValidationException::withMessages(['code' => 'MFA ist für dieses Konto bereits eingerichtet.']);
        }

        if (blank($user->two_factor_secret)) {
            $secret = TOTP::generate(new InternalClock, 20)->getSecret();
            $user->forceFill([
                'two_factor_secret' => $secret,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'two_factor_last_used_timestep' => null,
            ])->save();
        }

        $secret = (string) $user->fresh()->two_factor_secret;

        return [
            'secret' => $secret,
            'provisioning_uri' => $this->totp($user, $secret)->getProvisioningUri(),
        ];
    }

    /** @return list<string> */
    public function confirmEnrollment(User $user, string $code): array
    {
        $secret = (string) $user->two_factor_secret;
        $timestep = $secret === '' ? null : $this->matchingTimestep($secret, $code);
        if ($timestep === null) {
            throw ValidationException::withMessages(['code' => 'Der Bestätigungscode ist ungültig.']);
        }

        $recoveryCodes = $this->generateRecoveryCodes();
        $user->forceFill([
            'two_factor_recovery_codes' => array_map($this->hashRecoveryCode(...), $recoveryCodes),
            'two_factor_confirmed_at' => now(),
            'two_factor_last_used_timestep' => $timestep,
        ])->save();

        $this->audit->record('auth.mfa_enabled', $user, $user, [
            'recovery_code_count' => count($recoveryCodes),
        ]);

        return $recoveryCodes;
    }

    public function verifyAndConsume(User $user, string $code): ?MfaFactor
    {
        $code = trim($code);

        return DB::transaction(function () use ($user, $code): ?MfaFactor {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            if (! $this->isEnabled($lockedUser)) {
                return null;
            }

            if (preg_match('/^\d{6}$/', $code) === 1) {
                $timestep = $this->matchingTimestep((string) $lockedUser->two_factor_secret, $code);
                if ($timestep !== null && ($lockedUser->two_factor_last_used_timestep === null || $timestep > $lockedUser->two_factor_last_used_timestep)) {
                    $lockedUser->forceFill(['two_factor_last_used_timestep' => $timestep])->save();

                    return MfaFactor::Totp;
                }
            }

            $recoveryHashes = $lockedUser->two_factor_recovery_codes;
            if (! is_array($recoveryHashes)) {
                return null;
            }

            $candidateHash = $this->hashRecoveryCode($code);
            foreach ($recoveryHashes as $index => $storedHash) {
                if (is_string($storedHash) && hash_equals($storedHash, $candidateHash)) {
                    unset($recoveryHashes[$index]);
                    $lockedUser->forceFill(['two_factor_recovery_codes' => array_values($recoveryHashes)])->save();
                    $this->audit->record('auth.mfa_recovery_code_used', $lockedUser, $lockedUser, [
                        'remaining_recovery_codes' => count($recoveryHashes),
                    ]);

                    return MfaFactor::RecoveryCode;
                }
            }

            return null;
        });
    }

    public function remainingRecoveryCodes(User $user): int
    {
        return is_array($user->two_factor_recovery_codes) ? count($user->two_factor_recovery_codes) : 0;
    }

    public function reset(User $user, ?User $actor, ?string $actorType = null): int
    {
        return DB::transaction(function () use ($user, $actor, $actorType): int {
            $user->forceFill([
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'two_factor_last_used_timestep' => null,
                'remember_token' => null,
            ])->save();
            $revokedSessions = DB::table('sessions')->where('user_id', $user->id)->delete();
            $this->audit->record('identity.user.mfa_reset', $user, $actor, [
                'revoked_sessions' => $revokedSessions,
            ], $actorType);

            return $revokedSessions;
        });
    }

    private function totp(User $user, string $secret): TOTP
    {
        $totp = TOTP::createFromSecret($secret, new InternalClock);
        $totp->setIssuer((string) config('app.name', 'Healthcare Node Registry'));
        $totp->setLabel($user->email);

        return $totp;
    }

    private function matchingTimestep(string $secret, string $code): ?int
    {
        if (preg_match('/^\d{6}$/', $code) !== 1) {
            return null;
        }

        $totp = TOTP::createFromSecret($secret, new InternalClock);
        $timestamp = now()->timestamp;
        foreach ([0, -30, 30] as $offset) {
            $candidateTimestamp = $timestamp + $offset;
            if ($candidateTimestamp >= 0 && $totp->verify($code, $candidateTimestamp)) {
                return intdiv($candidateTimestamp, 30);
            }
        }

        return null;
    }

    /** @return list<string> */
    private function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($index = 0; $index < self::RECOVERY_CODE_COUNT; $index++) {
            $value = strtoupper(bin2hex(random_bytes(10)));
            $codes[] = implode('-', str_split($value, 4));
        }

        return $codes;
    }

    private function hashRecoveryCode(string $code): string
    {
        $normalized = strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', $code));

        return hash_hmac('sha256', $normalized, (string) config('app.key'));
    }
}
