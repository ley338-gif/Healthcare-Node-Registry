<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Support\RbacBootstrapper;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class SettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
        $this->app->make(RbacBootstrapper::class)->ensureSystemAdministratorRole();
    }

    public function test_administrator_can_open_user_management_below_settings(): void
    {
        $administrator = $this->administrator();

        $this->actingAs($administrator)->get('/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Index')
                ->where('canManageUsers', true)
                ->where('canManageRoles', true)
                ->has('users.data', 1)
                ->has('roles'));
    }

    public function test_user_without_management_permissions_cannot_open_settings(): void
    {
        $this->actingAs(User::factory()->create())->get('/settings')->assertForbidden();
    }

    public function test_administrator_can_create_user_with_role_and_audit_event(): void
    {
        $administrator = $this->administrator();
        $role = Role::query()->create(['name' => 'registry-editor', 'display_name' => 'Registry Editor']);

        $this->actingAs($administrator)->post('/settings/users', [
            'name' => 'Ada Lovelace',
            'email' => 'ADA@example.test',
            'password' => 'Strong-Managed-Password-2026!',
            'password_confirmation' => 'Strong-Managed-Password-2026!',
            'is_active' => true,
            'role_ids' => [$role->id],
        ])->assertRedirect()->assertSessionHas('success');

        $user = User::query()->where('email', 'ada@example.test')->firstOrFail();
        self::assertTrue($user->roles()->whereKey($role->id)->exists());
        self::assertTrue(Hash::check('Strong-Managed-Password-2026!', $user->password));
        self::assertTrue(SecurityEvent::query()->where('event_type', 'identity.user.created')->where('subject_public_id', $user->public_id)->exists());
    }

    public function test_administrator_cannot_deactivate_own_account(): void
    {
        $administrator = $this->administrator();

        $this->actingAs($administrator)->put("/settings/users/{$administrator->public_id}", [
            'name' => $administrator->name,
            'email' => $administrator->email,
            'is_active' => false,
            'role_ids' => $administrator->roles()->pluck('roles.id')->all(),
        ])->assertSessionHasErrors('is_active');

        self::assertTrue($administrator->fresh()->is_active);
    }

    public function test_last_active_system_administrator_is_protected(): void
    {
        $administrator = $this->administrator();
        $manager = User::factory()->create();
        $managerRole = Role::query()->create(['name' => 'identity-manager', 'display_name' => 'Identity Manager']);
        $managerRole->permissions()->sync(Permission::query()->whereIn('name', ['users.manage', 'roles.manage'])->pluck('id'));
        $manager->roles()->attach($managerRole);

        $this->actingAs($manager)->put("/settings/users/{$administrator->public_id}", [
            'name' => $administrator->name,
            'email' => $administrator->email,
            'is_active' => false,
            'role_ids' => $administrator->roles()->pluck('roles.id')->all(),
        ])->assertSessionHasErrors('role_ids');

        self::assertTrue($administrator->fresh()->is_active);
    }

    public function test_password_reset_revokes_existing_sessions(): void
    {
        $administrator = $this->administrator();
        $user = User::factory()->create();
        DB::table('sessions')->insert([
            'id' => 'managed-user-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'test',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($administrator)->put("/settings/users/{$user->public_id}/password", [
            'password' => 'New-Strong-Managed-Password-2026!',
            'password_confirmation' => 'New-Strong-Managed-Password-2026!',
        ])->assertRedirect()->assertSessionHas('success');

        self::assertTrue(Hash::check('New-Strong-Managed-Password-2026!', $user->fresh()->password));
        $this->assertDatabaseMissing('sessions', ['id' => 'managed-user-session']);
        $this->assertDatabaseHas('security_events', ['event_type' => 'identity.user.password_reset', 'subject_public_id' => $user->public_id]);
    }

    public function test_roles_can_be_managed_but_system_administrator_is_protected(): void
    {
        $administrator = $this->administrator();
        $permission = Permission::query()->where('name', 'registry.view')->firstOrFail();

        $this->actingAs($administrator)->post('/settings/roles', [
            'name' => 'registry-reader',
            'display_name' => 'Registry Reader',
            'permission_ids' => [$permission->id],
        ])->assertRedirect()->assertSessionHas('success');

        $role = Role::query()->where('name', 'registry-reader')->firstOrFail();
        $this->actingAs($administrator)->delete("/settings/roles/{$role->id}")->assertRedirect();
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);

        $systemRole = Role::query()->where('name', 'system-administrator')->firstOrFail();
        $this->actingAs($administrator)->delete("/settings/roles/{$systemRole->id}")
            ->assertSessionHasErrors('role');
        $this->assertDatabaseHas('roles', ['id' => $systemRole->id]);
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());

        return $user;
    }
}
