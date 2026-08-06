<?php

namespace Tests\Feature;

use App\Models\DiscoveredHost;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DiscoveryPromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_confirmed_finding_can_be_promoted_to_a_new_system(): void
    {
        $user = $this->createPromoter();
        $organization = Organization::query()->create(['name' => 'Musterklinikum Nord']);
        $host = DiscoveredHost::factory()->create([
            'ip_address' => '192.168.20.10',
            'hostname' => 'pacs-archive-01',
            'confidence_percentage' => 90,
        ]);

        $response = $this->actingAs($user)->post("/discovery/hosts/{$host->public_id}/promote", [
            'action' => 'create',
            'name' => 'PACS-ARCHIVE-01',
            'system_type' => 'pacs',
            'organization_id' => $organization->id,
            'operational_status' => 'active',
            'ae_title' => 'PACS01',
            'port' => 11112,
            'role' => 'scp',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('systems', ['name' => 'PACS-ARCHIVE-01', 'organization_id' => $organization->id, 'ip_address' => '192.168.20.10']);
        $system = System::query()->where('name', 'PACS-ARCHIVE-01')->firstOrFail();
        $this->assertDatabaseHas('dicom_nodes', ['system_id' => $system->id, 'ae_title' => 'PACS01', 'port' => 11112]);
        $this->assertDatabaseHas('discovered_hosts', ['id' => $host->id, 'status' => DiscoveredHost::STATUS_CONFIRMED, 'system_id' => $system->id]);
        $this->assertDatabaseHas('security_events', ['event_type' => 'discovery.system.promoted']);
    }

    public function test_promoting_to_an_existing_system_adds_an_endpoint_without_creating_a_duplicate_system(): void
    {
        $user = $this->createPromoter();
        $system = System::factory()->create(['ip_address' => '192.168.20.10']);
        $host = DiscoveredHost::factory()->create(['ip_address' => '192.168.20.10']);

        $this->actingAs($user)->post("/discovery/hosts/{$host->public_id}/promote", [
            'action' => 'update_existing',
            'existing_system_id' => $system->id,
            'name' => $system->name,
            'system_type' => $system->system_type,
            'organization_id' => $system->organization_id,
            'operational_status' => 'active',
            'ae_title' => 'PACS02',
            'port' => 104,
            'role' => 'scp',
        ]);

        $this->assertDatabaseCount('systems', 1);
        $this->assertDatabaseHas('dicom_nodes', ['system_id' => $system->id, 'ae_title' => 'PACS02']);
    }

    public function test_duplicate_detection_finds_a_matching_ip_address(): void
    {
        $user = $this->createPromoter();
        $existing = System::factory()->create(['ip_address' => '192.168.20.10']);
        $host = DiscoveredHost::factory()->create(['ip_address' => '192.168.20.10']);

        $response = $this->actingAs($user)->getJson("/discovery/hosts/{$host->public_id}/promotion-data");

        $response->assertOk();
        $response->assertJsonFragment(['type' => 'ip_address']);
        self::assertSame($existing->id, $response->json('duplicates.0.system.id'));
    }

    public function test_a_user_without_registry_manage_permission_cannot_promote(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $ids = Permission::query()->whereIn('name', ['discovery.manage'])->pluck('id');
        $role = Role::query()->create(['name' => 'discovery-only', 'display_name' => 'Discovery Only']);
        $role->permissions()->sync($ids);
        $user->roles()->attach($role);

        $host = DiscoveredHost::factory()->create();

        $this->actingAs($user)->getJson("/discovery/hosts/{$host->public_id}/promotion-data")->assertForbidden();
    }

    private function createPromoter(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $ids = Permission::query()->whereIn('name', ['discovery.manage', 'registry.manage', 'registry.view'])->pluck('id');
        $role = Role::query()->create(['name' => 'discovery-promoter', 'display_name' => 'Discovery Promoter']);
        $role->permissions()->sync($ids);
        $user->roles()->attach($role);

        return $user;
    }
}
