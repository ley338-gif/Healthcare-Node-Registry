<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DiscoveryPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_the_discovery_dashboard(): void
    {
        $this->get('/discovery')->assertRedirect('/login');
    }

    public function test_user_without_discovery_permission_is_forbidden(): void
    {
        $this->seed();
        $user = User::factory()->create();

        $this->actingAs($user)->get('/discovery')->assertForbidden();
    }

    public function test_user_with_view_permission_can_view_but_not_create_runs(): void
    {
        $user = $this->createUserWithPermissions(['discovery.view']);

        $this->actingAs($user)->get('/discovery')->assertOk();
        $this->actingAs($user)->get('/discovery/runs/create')->assertForbidden();
    }

    public function test_user_with_run_permission_can_open_the_wizard(): void
    {
        $user = $this->createUserWithPermissions(['discovery.run']);

        $this->actingAs($user)->get('/discovery/runs/create')->assertOk();
    }

    public function test_default_viewer_role_seeded_by_the_application_cannot_start_runs(): void
    {
        $this->seed();
        $viewerRole = Role::query()->where('name', 'user')->firstOrFail();
        $user = User::factory()->create();
        $user->roles()->attach($viewerRole);

        $this->actingAs($user)->get('/discovery')->assertOk();
        $this->actingAs($user)->get('/discovery/runs/create')->assertForbidden();
    }

    /** @param list<string> $permissionNames */
    private function createUserWithPermissions(array $permissionNames): User
    {
        $this->seed();

        $user = User::factory()->create();
        $ids = Permission::query()->whereIn('name', $permissionNames)->pluck('id');
        $role = Role::query()->create(['name' => 'test-role-'.uniqid(), 'display_name' => 'Testrolle']);
        $role->permissions()->sync($ids);
        $user->roles()->attach($role);

        return $user;
    }
}
