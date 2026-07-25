<?php

namespace Tests\Feature;

use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CreateInitialAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_first_administrator_and_security_event(): void
    {
        $this->artisan('registry:create-admin', [
            '--name' => 'Synthetic Initial Admin',
            '--email' => 'initial-admin@example.test',
            '--no-confirmation' => true,
        ])
            ->expectsQuestion('Passwort (mindestens 14 Zeichen)', 'Strong-Test-Password!42')
            ->expectsQuestion('Passwort bestätigen', 'Strong-Test-Password!42')
            ->assertSuccessful();

        $user = User::query()->where('email', 'initial-admin@example.test')->firstOrFail();

        $this->assertTrue($user->hasRole('system-administrator'));
        $this->assertDatabaseHas('security_events', [
            'event_type' => 'identity.initial_admin_created',
            'subject_public_id' => $user->public_id,
        ]);
    }

    public function test_it_refuses_a_second_initial_administrator(): void
    {
        $this->artisan('registry:create-admin', [
            '--name' => 'Synthetic Initial Admin',
            '--email' => 'initial-admin@example.test',
            '--no-confirmation' => true,
        ])
            ->expectsQuestion('Passwort (mindestens 14 Zeichen)', 'Strong-Test-Password!42')
            ->expectsQuestion('Passwort bestätigen', 'Strong-Test-Password!42')
            ->assertSuccessful();

        $this->artisan('registry:create-admin', [
            '--name' => 'Second Admin',
            '--email' => 'second-admin@example.test',
            '--no-confirmation' => true,
        ])->assertFailed();

        $this->assertSame(1, User::query()->count());
        $this->assertSame(1, SecurityEvent::query()->count());
    }
}
