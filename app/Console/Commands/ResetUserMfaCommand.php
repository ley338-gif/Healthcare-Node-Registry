<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Identity\MultiFactorAuthenticationService;
use Illuminate\Console\Command;

final class ResetUserMfaCommand extends Command
{
    protected $signature = 'registry:reset-mfa {email : E-Mail-Adresse des Kontos} {--force : Sicherheitsabfrage überspringen}';

    protected $description = 'Setzt MFA für ein lokales Konto kontrolliert zurück und widerruft seine Sitzungen';

    public function handle(MultiFactorAuthenticationService $mfa): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $user = User::query()->where('email', $email)->first();
        if (! $user instanceof User) {
            $this->error('Konto nicht gefunden.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("MFA für {$user->email} zurücksetzen und alle Sitzungen beenden?")) {
            $this->warn('Abgebrochen.');

            return self::FAILURE;
        }

        $revokedSessions = $mfa->reset($user, null, 'console');
        $this->info("MFA wurde zurückgesetzt; widerrufene Sitzungen: {$revokedSessions}.");

        return self::SUCCESS;
    }
}
