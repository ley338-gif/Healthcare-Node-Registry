<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Services\Identity\MultiFactorAuthenticationService;
use App\Support\RbacBootstrapper;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use OTPHP\InternalClock;
use OTPHP\TOTP;
use Tests\TestCase;

final class MultiFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
        CarbonImmutable::setTestNow('2026-08-09 12:00:15 UTC');
        $this->app->make(RbacBootstrapper::class)->ensureSystemAdministratorRole();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_privileged_user_must_enroll_before_registry_access(): void
    {
        $administrator = $this->administrator();

        $this->post('/login', $this->credentials($administrator))
            ->assertRedirect('/mfa/setup');
        $this->assertAuthenticatedAs($administrator);

        $this->get('/')->assertRedirect('/mfa/setup');
        $this->get('/mfa/setup')->assertOk();

        $administrator->refresh();
        self::assertNotNull($administrator->two_factor_secret);
        self::assertNotSame($administrator->two_factor_secret, DB::table('users')->where('id', $administrator->id)->value('two_factor_secret'));
    }

    public function test_role_with_sensitive_permission_also_requires_mfa(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Correct-Test-Password!')]);
        $role = Role::query()->create(['name' => 'registry-editor', 'display_name' => 'Registry Editor']);
        $role->permissions()->attach(Permission::query()->where('name', 'registry.manage')->firstOrFail());
        $user->roles()->attach($role);

        $this->post('/login', $this->credentials($user))->assertRedirect('/mfa/setup');
    }

    public function test_enrollment_enables_totp_and_displays_recovery_codes_once(): void
    {
        $administrator = $this->administrator();
        $this->post('/login', $this->credentials($administrator))->assertRedirect('/mfa/setup');
        $this->get('/mfa/setup')->assertOk();
        $code = $this->currentCode($administrator->fresh());

        $this->post('/mfa/setup', ['code' => $code])->assertRedirect('/mfa/recovery-codes');

        $administrator->refresh();
        self::assertNotNull($administrator->two_factor_confirmed_at);
        self::assertCount(8, $administrator->two_factor_recovery_codes ?? []);
        $this->get('/mfa/recovery-codes')->assertOk();
        $this->get('/mfa/recovery-codes')->assertRedirect('/');
        $this->assertDatabaseHas('security_events', [
            'event_type' => 'auth.mfa_enabled',
            'subject_public_id' => $administrator->public_id,
        ]);
    }

    public function test_enabled_mfa_requires_challenge_before_authentication(): void
    {
        $administrator = $this->enabledAdministrator();
        Auth::guard('web')->logout();

        $this->post('/login', $this->credentials($administrator))->assertRedirect('/mfa/challenge');
        $this->assertGuest();
        $this->post('/mfa/challenge', ['code' => $this->currentCode($administrator)])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($administrator);
        $this->assertDatabaseHas('security_events', [
            'event_type' => 'auth.mfa_verified',
            'subject_public_id' => $administrator->public_id,
        ]);
    }

    public function test_totp_code_cannot_be_replayed(): void
    {
        $administrator = $this->enabledAdministrator();
        Auth::guard('web')->logout();
        $code = $this->currentCode($administrator);

        $this->post('/login', $this->credentials($administrator));
        $this->post('/mfa/challenge', ['code' => $code])->assertRedirect('/');
        Auth::guard('web')->logout();

        $this->post('/login', $this->credentials($administrator));
        $this->post('/mfa/challenge', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_recovery_code_is_single_use(): void
    {
        $administrator = $this->enabledAdministrator();
        $service = $this->app->make(MultiFactorAuthenticationService::class);
        $service->reset($administrator, $administrator);
        $enrollment = $service->enrollment($administrator->fresh());
        $plainCodes = $service->confirmEnrollment($administrator->fresh(), $this->codeForSecret($enrollment['secret']));
        $recoveryCode = $plainCodes[0];
        Auth::guard('web')->logout();

        $this->post('/login', $this->credentials($administrator));
        $this->post('/mfa/challenge', ['code' => $recoveryCode])->assertRedirect('/');
        self::assertSame(7, $service->remainingRecoveryCodes($administrator->fresh()));
        Auth::guard('web')->logout();

        $this->post('/login', $this->credentials($administrator));
        $this->post('/mfa/challenge', ['code' => $recoveryCode])->assertSessionHasErrors('code');
    }

    public function test_administrator_can_reset_mfa_and_revoke_sessions(): void
    {
        $actor = $this->administrator();
        $target = $this->enabledAdministrator();
        DB::table('sessions')->insert([
            'id' => 'mfa-reset-session',
            'user_id' => $target->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'test',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($actor)->delete("/settings/users/{$target->public_id}/mfa")
            ->assertRedirect()->assertSessionHas('success');

        $target->refresh();
        self::assertNull($target->two_factor_secret);
        self::assertNull($target->two_factor_confirmed_at);
        $this->assertDatabaseMissing('sessions', ['id' => 'mfa-reset-session']);
        $this->assertDatabaseHas('security_events', [
            'event_type' => 'identity.user.mfa_reset',
            'subject_public_id' => $target->public_id,
        ]);
    }

    public function test_console_break_glass_reset_is_audited(): void
    {
        $administrator = $this->enabledAdministrator();

        $this->artisan('registry:reset-mfa', ['email' => $administrator->email, '--force' => true])
            ->assertSuccessful();

        self::assertNull($administrator->fresh()->two_factor_secret);
        $event = SecurityEvent::query()->where('event_type', 'identity.user.mfa_reset')->latest('id')->firstOrFail();
        self::assertSame('console', $event->actor_type);
    }

    public function test_resetting_own_mfa_invalidates_current_session(): void
    {
        $administrator = $this->enabledAdministrator();

        $this->actingAs($administrator)->delete("/settings/users/{$administrator->public_id}/mfa")
            ->assertRedirect('/login');

        $this->assertGuest();
        self::assertNull($administrator->fresh()->two_factor_secret);
    }

    private function administrator(): User
    {
        $user = User::factory()->create(['password' => Hash::make('Correct-Test-Password!')]);
        $user->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());

        return $user;
    }

    private function enabledAdministrator(): User
    {
        $user = $this->administrator();
        $service = $this->app->make(MultiFactorAuthenticationService::class);
        $enrollment = $service->enrollment($user);
        $service->confirmEnrollment($user->fresh(), $this->codeForSecret($enrollment['secret']));
        CarbonImmutable::setTestNow(now()->addSeconds(30));

        return $user->fresh();
    }

    /** @return array{email: string, password: string} */
    private function credentials(User $user): array
    {
        return ['email' => $user->email, 'password' => 'Correct-Test-Password!'];
    }

    private function currentCode(User $user): string
    {
        return $this->codeForSecret((string) $user->two_factor_secret);
    }

    private function codeForSecret(string $secret): string
    {
        return TOTP::createFromSecret($secret, new InternalClock)->at(now()->timestamp);
    }
}
