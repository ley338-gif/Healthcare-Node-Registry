<?php

namespace Tests\Feature;

use App\Models\DiscoveredHost;
use App\Models\Role;
use App\Models\System;
use App\Models\SystemNetworkInterface;
use App\Models\User;
use App\Services\Discovery\RegistryPromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SystemNetworkInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_network_fields_create_a_primary_interface(): void
    {
        $system = System::factory()->create([
            'hostname' => 'pacs-node-1',
            'fqdn' => 'pacs-node-1.example.test',
            'ip_address' => '10.10.1.20',
        ]);

        $this->assertDatabaseHas('system_network_interfaces', [
            'system_id' => $system->id,
            'hostname' => 'pacs-node-1',
            'fqdn' => 'pacs-node-1.example.test',
            'ip_address' => '10.10.1.20',
            'is_primary' => true,
        ]);
    }

    public function test_migration_backfills_existing_legacy_network_fields(): void
    {
        $system = System::factory()->create([
            'hostname' => 'legacy-pacs',
            'fqdn' => 'legacy-pacs.example.test',
            'ip_address' => '10.20.30.40',
        ]);
        $migration = require database_path('migrations/2026_08_08_140000_create_system_network_interfaces_table.php');
        $migration->down();
        $migration->up();

        $this->assertDatabaseHas('system_network_interfaces', [
            'system_id' => $system->id,
            'interface_label' => 'Primärschnittstelle',
            'hostname' => 'legacy-pacs',
            'fqdn' => 'legacy-pacs.example.test',
            'ip_address' => '10.20.30.40',
            'is_primary' => true,
        ]);
    }

    public function test_administrator_can_manage_multiple_interfaces_and_primary_mirror(): void
    {
        $system = System::factory()->create(['hostname' => 'node-1', 'fqdn' => null, 'ip_address' => '10.10.1.20']);
        $original = $system->networkInterfaces()->firstOrFail();
        $user = $this->administrator();

        $this->actingAs($user)->post("/systems/{$system->public_id}/network-interfaces", [
            'interface_label' => 'Cluster-VIP',
            'hostname' => 'pacs-vip',
            'fqdn' => 'pacs-vip.example.test',
            'ip_address' => '10.10.1.100',
            'is_primary' => true,
        ])->assertRedirect();

        $vip = SystemNetworkInterface::query()->where('system_id', $system->id)->where('interface_label', 'Cluster-VIP')->firstOrFail();
        self::assertFalse($original->fresh()->is_primary);
        self::assertTrue($vip->is_primary);
        self::assertSame('10.10.1.100', $system->fresh()->ip_address);

        $this->actingAs($user)->put("/system-network-interfaces/{$vip->public_id}", [
            'interface_label' => 'Cluster-VIP',
            'hostname' => 'pacs-vip-new',
            'fqdn' => null,
            'ip_address' => '10.10.1.101',
            'is_primary' => true,
        ])->assertRedirect();
        self::assertSame('pacs-vip-new', $system->fresh()->hostname);

        $this->actingAs($user)->delete("/system-network-interfaces/{$vip->public_id}")->assertRedirect();
        self::assertTrue($original->fresh()->is_primary);
        self::assertSame('node-1', $system->fresh()->hostname);
    }

    public function test_interface_requires_an_endpoint_and_unique_label(): void
    {
        $system = System::factory()->create();
        $user = $this->administrator();
        $label = $system->networkInterfaces()->firstOrFail()->interface_label;

        $this->actingAs($user)->post("/systems/{$system->public_id}/network-interfaces", [
            'interface_label' => $label,
            'hostname' => '',
            'fqdn' => '',
            'ip_address' => '',
            'is_primary' => false,
        ])->assertSessionHasErrors(['interface_label', 'hostname', 'fqdn', 'ip_address']);
    }

    public function test_read_only_user_cannot_manage_interfaces(): void
    {
        $system = System::factory()->create();
        $interface = $system->networkInterfaces()->firstOrFail();
        $user = User::factory()->create();

        $this->actingAs($user)->delete("/system-network-interfaces/{$interface->public_id}")->assertForbidden();
    }

    public function test_secondary_interfaces_are_searchable_and_used_for_discovery_duplicates(): void
    {
        $system = System::factory()->create();
        $system->networkInterfaces()->create([
            'interface_label' => 'Management',
            'hostname' => 'pacs-management',
            'fqdn' => 'pacs-management.example.test',
            'ip_address' => '10.99.1.25',
            'is_primary' => false,
        ]);

        $this->actingAs($this->administrator())
            ->get('/systems?search=pacs-management')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('items.total', 1)->where('items.data.0.public_id', $system->public_id));

        $host = DiscoveredHost::factory()->create(['ip_address' => '10.99.1.25', 'hostname' => 'pacs-management']);
        $matches = app(RegistryPromotionService::class)->findDuplicates($host, 'Neues PACS', 'NEW_PACS', 104);

        self::assertNotEmpty(array_filter($matches, static fn (array $match): bool => $match['system']->is($system) && $match['type'] === 'ip_address'));
    }

    private function administrator(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());

        return $user;
    }
}
