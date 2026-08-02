<?php

namespace App\Console\Commands;

use App\Models\SecurityEvent;
use App\Models\User;
use App\Support\RbacBootstrapper;
use App\Support\RegistryPasswordPolicy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

final class CreateInitialAdminCommand extends Command
{
    protected $signature = 'registry:create-admin
        {--name= : Vollständiger Anzeigename}
        {--email= : E-Mail-Adresse}
        {--no-confirmation : Abschließende Bestätigung überspringen}';

    protected $description = 'Create the first system administrator securely';

    public function handle(RbacBootstrapper $rbac): int
    {
        if ($this->administratorExists()) {
            $this->error('Es existiert bereits ein Systemadministrator.');

            return self::FAILURE;
        }

        $name = trim((string) ($this->option('name') ?: $this->ask('Name')));
        $email = Str::lower(trim((string) ($this->option('email') ?: $this->ask('E-Mail-Adresse'))));

        $identity = Validator::make(
            ['name' => $name, 'email' => $email],
            [
                'name' => ['required', 'string', 'min:2', 'max:160'],
                'email' => ['required', 'string', 'email', 'max:254', 'unique:users,email'],
            ],
        );

        if ($identity->fails()) {
            foreach ($identity->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $password = (string) $this->secret('Passwort (mindestens 14 Zeichen)');
        $confirmation = (string) $this->secret('Passwort bestätigen');

        if (! hash_equals($password, $confirmation)) {
            $this->error('Die Passwörter stimmen nicht überein.');

            return self::FAILURE;
        }

        $passwordCheck = Validator::make(
            ['password' => $password],
            ['password' => RegistryPasswordPolicy::rules(false)],
        );

        if ($passwordCheck->fails()) {
            foreach ($passwordCheck->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        if (! $this->option('no-confirmation')
            && ! $this->confirm("Systemadministrator {$email} anlegen?", false)) {
            $this->warn('Vorgang abgebrochen.');

            return self::FAILURE;
        }

        try {
            $user = DB::transaction(function () use ($name, $email, $password, $rbac): User {
                $role = $rbac->ensureSystemAdministratorRole();

                $user = User::query()->create([
                    'public_id' => (string) Str::uuid7(),
                    'name' => $name,
                    'email' => $email,
                    'email_verified_at' => now(),
                    'password' => Hash::make($password),
                    'is_active' => true,
                ]);
                $user->roles()->attach($role->id);

                SecurityEvent::query()->create([
                    'event_id' => (string) Str::uuid7(),
                    'event_type' => 'identity.initial_admin_created',
                    'actor_type' => 'console',
                    'subject_type' => User::class,
                    'subject_public_id' => $user->public_id,
                    'metadata' => ['command' => 'registry:create-admin'],
                    'occurred_at' => now(),
                ]);

                return $user;
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Konto konnte nicht angelegt werden. Details stehen im technischen Log.');

            return self::FAILURE;
        }

        $this->info("Systemadministrator {$user->email} wurde angelegt.");
        $this->line('Das Passwort wurde nicht ausgegeben oder protokolliert.');

        return self::SUCCESS;
    }

    private function administratorExists(): bool
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'system-administrator'))
            ->exists();
    }
}
