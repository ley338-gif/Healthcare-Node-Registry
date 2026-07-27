<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SystemManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_manager_can_create_a_system(): void
    {
        $user = $this->createRegistryManager();

        $organization = Organization::query()->create([
            'name' => 'Musterklinik',
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/systems', [
                'organization_id' => $organization->id,
                'site_id' => null,
                'department_id' => null,
                'name' => 'PACS Produktion',
                'system_type' => 'pacs',
                'status' => 'active',
                'hostname' => 'pacs01',
                'fqdn' => 'pacs01.example.local',
                'ip_address' => '10.10.10.20',
                'vendor' => 'VISUS',
                'product' => 'JiveX',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('systems', [
            'organization_id' => $organization->id,
            'name' => 'PACS Produktion',
            'system_type' => 'pacs',
            'status' => 'active',
            'hostname' => 'pacs01',
            'ip_address' => '10.10.10.20',
        ]);

        $this->assertDatabaseHas('security_events', [
            'event_type' => 'registry.system.created',
        ]);
    }

    public function test_invalid_system_data_is_rejected(): void
    {
        $user = $this->createRegistryManager();

        $response = $this
            ->actingAs($user)
            ->post('/systems', [
                'organization_id' => 999999,
                'name' => '',
                'system_type' => '',
                'status' => '',
                'ip_address' => 'keine-ip-adresse',
            ]);

        $response->assertSessionHasErrors([
            'organization_id',
            'name',
            'system_type',
            'status',
            'ip_address',
        ]);

        $this->assertDatabaseCount('systems', 0);
    }

    public function test_registry_manager_can_update_a_system(): void
    {
        $user = $this->createRegistryManager();

        $system = System::factory()->create([
            'name' => 'Altes PACS',
            'status' => 'active',
        ]);

        $response = $this
            ->actingAs($user)
            ->put("/systems/{$system->public_id}", [
                'organization_id' => $system->organization_id,
                'site_id' => $system->site_id,
                'department_id' => $system->department_id,
                'name' => 'Neues PACS',
                'system_type' => 'pacs',
                'status' => 'maintenance',
                'hostname' => 'pacs02',
                'ip_address' => '10.20.30.40',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('systems', [
            'id' => $system->id,
            'name' => 'Neues PACS',
            'status' => 'maintenance',
            'hostname' => 'pacs02',
            'ip_address' => '10.20.30.40',
        ]);

        $this->assertDatabaseHas('security_events', [
            'event_type' => 'registry.system.updated',
        ]);
    }

    public function test_registry_manager_can_archive_a_system(): void
    {
        $user = $this->createRegistryManager();
        $system = System::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post("/systems/{$system->public_id}/archive");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $system->refresh();

        $this->assertNotNull($system->archived_at);
        $this->assertSame('retired', $system->status);

        $this->assertDatabaseHas('security_events', [
            'event_type' => 'registry.system.archived',
        ]);
    }

    public function test_unprivileged_user_cannot_create_a_system(): void
    {
        $user = User::factory()->create();

        $organization = Organization::query()->create([
            'name' => 'Musterklinik',
        ]);

        $response = $this
            ->actingAs($user)
            ->post('/systems', [
                'organization_id' => $organization->id,
                'name' => 'PACS Produktion',
                'system_type' => 'pacs',
                'status' => 'active',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseCount('systems', 0);
    }

    private function createRegistryManager(): User
    {
        $this->seed();

        $user = User::factory()->create();

        $role = Role::query()
            ->where('name', 'system-administrator')
            ->firstOrFail();

        $user->roles()->attach($role);

        return $user;
    }
}
