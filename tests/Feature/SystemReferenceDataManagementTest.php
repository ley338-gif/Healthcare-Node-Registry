<?php

namespace Tests\Feature;

use App\Models\Manufacturer;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\System;
use App\Models\SystemStatus;
use App\Models\SystemType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class SystemReferenceDataManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_manager_can_view_seeded_system_reference_data(): void
    {
        $this->actingAs($this->settingsManager())->get('/settings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Settings/Index')
                ->where('canManageReferenceData', true)
                ->has('systemTypes', 11)
                ->has('systemStatuses', 5)
                ->has('manufacturers', 0));
    }

    public function test_settings_manager_can_create_and_update_a_system_type_with_audit(): void
    {
        $manager = $this->settingsManager();

        $this->actingAs($manager)->post('/settings/system-types', [
            'code' => 'vna',
            'label' => 'Vendor Neutral Archive',
            'sort_order' => 35,
            'is_active' => true,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $type = SystemType::query()->where('code', 'vna')->firstOrFail();
        $this->actingAs($manager)->put("/settings/system-types/{$type->public_id}", [
            'code' => 'changed_code_is_ignored',
            'label' => 'VNA',
            'sort_order' => 30,
            'is_active' => true,
        ])->assertRedirect()->assertSessionHasNoErrors();

        self::assertSame('vna', $type->fresh()->code);
        self::assertSame('VNA', $type->fresh()->label);
        $this->assertDatabaseHas('security_events', [
            'event_type' => 'settings.system_type.created',
            'subject_public_id' => $type->public_id,
        ]);
        $this->assertDatabaseHas('security_events', [
            'event_type' => 'settings.system_type.updated',
            'subject_public_id' => $type->public_id,
        ]);
    }

    public function test_settings_manager_can_create_a_system_status_with_audit(): void
    {
        $this->actingAs($this->settingsManager())->post('/settings/system-statuses', [
            'code' => 'commissioning',
            'label' => 'Inbetriebnahme',
            'sort_order' => 25,
            'is_active' => true,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $status = SystemStatus::query()->where('code', 'commissioning')->firstOrFail();
        $this->assertDatabaseHas('security_events', [
            'event_type' => 'settings.system_status.created',
            'subject_public_id' => $status->public_id,
        ]);
    }

    public function test_settings_manager_can_create_and_update_a_manufacturer_with_audit(): void
    {
        $manager = $this->settingsManager();

        $this->actingAs($manager)->post('/settings/manufacturers', [
            'code' => 'example_medical',
            'label' => 'Example Medical GmbH',
            'sort_order' => 20,
            'is_active' => true,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $manufacturer = Manufacturer::query()->where('code', 'example_medical')->firstOrFail();
        $this->actingAs($manager)->put("/settings/manufacturers/{$manufacturer->public_id}", [
            'code' => 'immutable_code',
            'label' => 'Example Medical AG',
            'sort_order' => 15,
            'is_active' => true,
        ])->assertRedirect()->assertSessionHasNoErrors();

        self::assertSame('example_medical', $manufacturer->fresh()->code);
        self::assertSame('Example Medical AG', $manufacturer->fresh()->label);
        $this->assertDatabaseHas('security_events', [
            'event_type' => 'settings.manufacturer.created',
            'subject_public_id' => $manufacturer->public_id,
        ]);
        $this->assertDatabaseHas('security_events', [
            'event_type' => 'settings.manufacturer.updated',
            'subject_public_id' => $manufacturer->public_id,
        ]);
    }

    public function test_inactive_manufacturer_cannot_be_assigned_but_existing_assignment_can_be_retained(): void
    {
        $manager = $this->settingsManager();
        $manufacturer = Manufacturer::query()->create([
            'code' => 'legacy_vendor',
            'label' => 'Legacy Vendor',
            'sort_order' => 200,
            'is_active' => true,
        ]);
        $system = System::factory()->create(['vendor' => $manufacturer->code]);

        $this->actingAs($manager)->put("/settings/manufacturers/{$manufacturer->public_id}", [
            'label' => $manufacturer->label,
            'sort_order' => $manufacturer->sort_order,
            'is_active' => false,
        ])->assertSessionHasNoErrors();

        $organization = Organization::query()->create(['name' => 'Hersteller-Testorganisation']);
        $this->actingAs($manager)->post('/systems', [
            'organization_id' => $organization->id,
            'name' => 'Neues Legacy-System',
            'system_type' => 'server',
            'status' => 'active',
            'vendor' => $manufacturer->code,
        ])->assertSessionHasErrors('vendor');

        $this->actingAs($manager)->put("/systems/{$system->public_id}", [
            'organization_id' => $system->organization_id,
            'site_id' => $system->site_id,
            'department_id' => $system->department_id,
            'name' => $system->name,
            'system_type' => $system->system_type,
            'status' => $system->status,
            'vendor' => $manufacturer->code,
        ])->assertSessionHasNoErrors();
    }

    public function test_inactive_type_cannot_be_assigned_but_existing_assignment_can_be_retained(): void
    {
        $manager = $this->settingsManager();
        $type = SystemType::query()->create([
            'code' => 'legacy_archive',
            'label' => 'Legacy-Archiv',
            'sort_order' => 200,
            'is_active' => true,
        ]);
        $system = System::factory()->create(['system_type' => $type->code]);

        $this->actingAs($manager)->put("/settings/system-types/{$type->public_id}", [
            'label' => $type->label,
            'sort_order' => $type->sort_order,
            'is_active' => false,
        ])->assertSessionHasNoErrors();

        $organization = Organization::query()->create(['name' => 'Neue Organisation']);
        $this->actingAs($manager)->post('/systems', [
            'organization_id' => $organization->id,
            'name' => 'Neues Legacy-System',
            'system_type' => $type->code,
            'status' => 'active',
        ])->assertSessionHasErrors('system_type');

        $this->actingAs($manager)->put("/systems/{$system->public_id}", [
            'organization_id' => $system->organization_id,
            'site_id' => $system->site_id,
            'department_id' => $system->department_id,
            'name' => 'Bestehendes Legacy-System',
            'system_type' => $type->code,
            'status' => $system->status,
        ])->assertSessionHasNoErrors();
    }

    public function test_protected_lifecycle_status_cannot_be_deactivated(): void
    {
        $status = SystemStatus::query()->where('code', 'retired')->firstOrFail();

        $this->actingAs($this->settingsManager())->put("/settings/system-statuses/{$status->public_id}", [
            'label' => $status->label,
            'sort_order' => $status->sort_order,
            'is_active' => false,
        ])->assertSessionHasErrors('is_active');

        self::assertTrue($status->fresh()->is_active);
    }

    public function test_user_without_settings_permission_cannot_manage_reference_data(): void
    {
        $type = SystemType::query()->where('code', 'pacs')->firstOrFail();
        $user = User::factory()->create();

        $this->actingAs($user)->post('/settings/system-types', [
            'code' => 'forbidden',
            'label' => 'Nicht erlaubt',
            'sort_order' => 100,
            'is_active' => true,
        ])->assertForbidden();

        $this->actingAs($user)->put("/settings/system-types/{$type->public_id}", [
            'label' => 'Nicht erlaubt',
            'sort_order' => 100,
            'is_active' => true,
        ])->assertForbidden();

        $this->actingAs($user)->post('/settings/manufacturers', [
            'code' => 'forbidden_vendor',
            'label' => 'Nicht erlaubt',
            'sort_order' => 100,
            'is_active' => true,
        ])->assertForbidden();
    }

    public function test_system_list_filters_and_searches_by_manufacturer_label(): void
    {
        $manager = $this->settingsManager();
        $manufacturer = Manufacturer::query()->create([
            'code' => 'example_medical',
            'label' => 'Example Medical GmbH',
            'sort_order' => 10,
            'is_active' => true,
        ]);
        $included = System::factory()->create(['name' => 'Hersteller-Treffer', 'vendor' => $manufacturer->code]);
        System::factory()->create(['name' => 'Anderes System']);

        $this->actingAs($manager)->get('/systems?manufacturer=example_medical')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('items.data', 1)
                ->where('items.data.0.public_id', $included->public_id)
                ->where('selectedSystem.vendor_label', 'Example Medical GmbH')
                ->where('filters.manufacturer', 'example_medical'));

        $this->actingAs($manager)->get('/systems?search=Example%20Medical')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('items.data', 1)
                ->where('items.data.0.public_id', $included->public_id));
    }

    public function test_system_forms_use_custom_reference_labels(): void
    {
        SystemType::query()->create([
            'code' => 'archive_gateway',
            'label' => 'Archiv-Gateway',
            'sort_order' => 15,
            'is_active' => true,
        ]);
        SystemStatus::query()->create([
            'code' => 'validation',
            'label' => 'In Validierung',
            'sort_order' => 15,
            'is_active' => true,
        ]);
        Manufacturer::query()->create([
            'code' => 'example_manufacturer',
            'label' => 'Beispielhersteller',
            'sort_order' => 15,
            'is_active' => true,
        ]);

        $this->actingAs($this->settingsManager())->get('/systems')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('systemTypes.1.value', 'archive_gateway')
                ->where('systemTypes.1.label', 'Archiv-Gateway')
                ->where('statuses.1.value', 'validation')
                ->where('statuses.1.label', 'In Validierung')
                ->where('manufacturers.0.value', 'example_manufacturer')
                ->where('manufacturers.0.label', 'Beispielhersteller'));
    }

    private function settingsManager(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $role = Role::query()->create([
            'name' => 'reference-data-manager-'.str()->random(8),
            'display_name' => 'Referenzdatenverwaltung',
        ]);
        $role->permissions()->sync(Permission::query()->whereIn('name', ['settings.manage', 'registry.manage'])->pluck('id'));
        $user->roles()->attach($role);

        return $user;
    }
}
