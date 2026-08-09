<?php

namespace Tests\Feature;

use App\Models\DiscoveryPortProfile;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DiscoveryPortProfileSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_and_update_an_audited_port_profile(): void
    {
        $manager = $this->createManager();

        $this->actingAs($manager)->post('/settings/discovery/port-profiles', [
            'name' => 'PACS eng',
            'description' => 'Gezielte PACS-Prüfung',
            'ports' => [
                ['port' => 11112, 'protocol' => 'tcp', 'label' => 'DICOM', 'is_dicom_candidate' => true, 'enabled' => true],
                ['port' => 443, 'protocol' => 'tcp', 'label' => 'HTTPS', 'is_dicom_candidate' => false, 'enabled' => false],
            ],
        ])->assertSessionHasNoErrors();

        $profile = DiscoveryPortProfile::query()->where('name', 'PACS eng')->firstOrFail();
        self::assertCount(2, $profile->ports);
        $this->assertDatabaseHas('security_events', ['event_type' => 'discovery.port_profile.created']);

        $this->actingAs($manager)->put("/settings/discovery/port-profiles/{$profile->public_id}", [
            'name' => 'PACS eng', 'description' => 'Aktualisiert', 'active' => false, 'is_default' => false,
            'ports' => $profile->ports,
        ])->assertSessionHasNoErrors();

        self::assertFalse($profile->fresh()->active);
        $this->assertDatabaseHas('security_events', ['event_type' => 'discovery.port_profile.updated']);
    }

    public function test_duplicate_ports_are_rejected(): void
    {
        $manager = $this->createManager();

        $this->actingAs($manager)->post('/settings/discovery/port-profiles', [
            'name' => 'Ungültig',
            'ports' => [
                ['port' => 11112, 'protocol' => 'tcp', 'label' => null, 'is_dicom_candidate' => true, 'enabled' => true],
                ['port' => 11112, 'protocol' => 'tcp', 'label' => null, 'is_dicom_candidate' => true, 'enabled' => true],
            ],
        ])->assertSessionHasErrors(['ports.1.port']);
    }

    public function test_wizard_receives_only_active_profiles_with_default_first(): void
    {
        $runner = $this->createManager(['discovery.manage', 'discovery.run']);
        DiscoveryPortProfile::query()->update(['is_default' => false]);
        $default = DiscoveryPortProfile::query()->create([
            'name' => 'Mein Standard', 'ports' => $this->ports(104), 'active' => true, 'is_default' => true,
        ]);
        DiscoveryPortProfile::query()->create([
            'name' => 'Deaktiviert', 'ports' => $this->ports(4242), 'active' => false, 'is_default' => false,
        ]);

        $this->actingAs($runner)->get('/discovery/runs/create')
            ->assertInertia(fn ($page) => $page
                ->where('portProfiles.0.public_id', $default->public_id)
                ->has('portProfiles', 2));
    }

    /** @return list<array<string, mixed>> */
    private function ports(int $port): array
    {
        return [['port' => $port, 'protocol' => 'tcp', 'label' => 'DICOM', 'is_dicom_candidate' => true, 'enabled' => true]];
    }

    /** @param list<string> $permissions */
    private function createManager(array $permissions = ['discovery.manage']): User
    {
        $this->seed();
        $user = User::factory()->create();
        $role = Role::query()->create(['name' => 'port-profile-manager-'.str()->random(6), 'display_name' => 'Port Profile Manager']);
        $role->permissions()->sync(Permission::query()->whereIn('name', $permissions)->pluck('id'));
        $user->roles()->attach($role);

        return $user;
    }
}
