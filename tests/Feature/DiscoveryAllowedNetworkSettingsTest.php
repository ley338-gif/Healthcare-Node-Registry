<?php

namespace Tests\Feature;

use App\Models\DiscoveryAllowedNetwork;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DiscoveryAllowedNetworkSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_administrator_can_add_and_remove_allowed_networks(): void
    {
        $user = $this->createAdmin();

        $this->actingAs($user)->post('/settings/discovery', [
            'cidr' => '172.20.0.0/16',
            'description' => 'Test-VLAN',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('discovery_allowed_networks', ['cidr' => '172.20.0.0/16', 'active' => true]);
        $this->assertDatabaseHas('security_events', ['event_type' => 'discovery.allowed_network.created']);

        $network = DiscoveryAllowedNetwork::query()->where('cidr', '172.20.0.0/16')->firstOrFail();

        $this->actingAs($user)->delete("/settings/discovery/{$network->public_id}")->assertRedirect();
        $this->assertDatabaseMissing('discovery_allowed_networks', ['id' => $network->id]);
    }

    public function test_an_invalid_cidr_is_rejected(): void
    {
        $user = $this->createAdmin();

        $response = $this->actingAs($user)->post('/settings/discovery', [
            'cidr' => 'not-a-network',
            'description' => null,
        ]);

        $response->assertSessionHasErrors(['cidr']);
    }

    public function test_a_user_without_manage_permission_cannot_view_the_settings_page(): void
    {
        $this->seed();
        $user = User::factory()->create();

        $this->actingAs($user)->get('/settings/discovery')->assertForbidden();
    }

    private function createAdmin(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $ids = Permission::query()->whereIn('name', ['discovery.manage'])->pluck('id');
        $role = Role::query()->create(['name' => 'discovery-admin', 'display_name' => 'Discovery Admin']);
        $role->permissions()->sync($ids);
        $user->roles()->attach($role);

        return $user;
    }
}
