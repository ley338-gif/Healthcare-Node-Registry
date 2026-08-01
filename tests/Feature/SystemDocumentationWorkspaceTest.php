<?php

namespace Tests\Feature;

use App\Models\RegistryDocument;
use App\Models\RegistryDocumentation;
use App\Models\RegistryDocumentVersion;
use App\Models\Role;
use App\Models\SecurityEvent;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SystemDocumentationWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_documentation_is_available_in_detail_and_selected_workspace(): void
    {
        $this->withoutVite();
        $this->seed();
        $user = $this->administrator();
        $system = System::factory()->create([
            'vendor' => 'Acme Medical',
            'product' => 'Clinical Archive',
            'version' => '4.2',
        ]);

        $this->actingAs($user)->post("/registry-documentation/systems/{$system->public_id}", [
            'documentation_type' => 'operations',
            'section' => 'operations',
            'title' => 'Betrieb',
            'content' => null,
            'structured_data' => [
                'operational_status' => 'Produktiv',
                'operating_hours' => '24/7',
                'maintenance_window' => 'Sonntag 02:00–04:00',
            ],
            'visibility' => 'internal',
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->get("/systems/{$system->public_id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('documentation', 1)
                ->where('documentation.0.section', 'operations')
                ->where('documentation.0.structured_data.operating_hours', '24/7'));

        $this->actingAs($user)->get("/systems?selected={$system->public_id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('documentation', 1)
                ->where('documentation.0.section', 'operations'));
    }

    public function test_system_master_data_is_not_duplicated_in_documentation(): void
    {
        $this->seed();
        $user = $this->administrator();
        $system = System::factory()->create(['vendor' => 'Vendor AG', 'hostname' => 'pacs.internal']);

        $this->actingAs($user)->post("/registry-documentation/systems/{$system->public_id}", [
            'documentation_type' => 'operations',
            'section' => 'product_support',
            'title' => 'Produkt und Hersteller',
            'content' => null,
            'structured_data' => ['support_contact' => 'support@example.test'],
            'visibility' => 'internal',
        ]);

        $data = RegistryDocumentation::query()->firstOrFail()->structured_data;
        $this->assertArrayNotHasKey('vendor', $data);
        $this->assertArrayNotHasKey('hostname', $data);
        $this->assertSame('Vendor AG', $system->fresh()?->vendor);
    }

    public function test_system_documentation_change_appears_in_shared_history(): void
    {
        $this->withoutVite();
        $this->seed();
        $user = $this->administrator();
        $system = System::factory()->create();
        $this->actingAs($user)->post("/registry-documentation/systems/{$system->public_id}", [
            'documentation_type' => 'operations',
            'section' => 'monitoring',
            'title' => 'Monitoring',
            'content' => null,
            'structured_data' => ['monitoring_present' => true, 'monitoring_system' => 'Zabbix'],
            'visibility' => 'internal',
        ]);

        $this->assertDatabaseHas('security_events', [
            'event_type' => 'documentation.updated',
            'subject_public_id' => $system->public_id,
        ]);
        $this->actingAs($user)->get("/systems/{$system->public_id}?history_type=documentation.updated")
            ->assertInertia(fn ($page) => $page
                ->has('history.data', 1)
                ->where('history.data.0.event_type', 'documentation.updated'));
        $this->assertSame(1, SecurityEvent::query()->where('event_type', 'documentation.updated')->count());
    }

    public function test_system_workspace_contains_only_documents_assigned_to_the_system(): void
    {
        $this->withoutVite();
        $this->seed();
        $user = $this->administrator();
        $system = System::factory()->create();
        $foreignSystem = System::factory()->create();
        $document = RegistryDocument::factory()->for($system, 'documentable')->create([
            'title' => 'Systemhandbuch',
            'valid_until' => now()->addDays(30)->toDateString(),
            'contract_reference' => 'SUP-2026-42',
        ]);
        $version = RegistryDocumentVersion::factory()->for($document, 'document')->create(['version_number' => 1]);
        $document->update(['current_version_id' => $version->id]);
        RegistryDocument::factory()->for($foreignSystem, 'documentable')->create(['title' => 'Fremdes Dokument']);

        $this->actingAs($user)->get("/systems/{$system->public_id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('documents', 1)
                ->where('documents.0.title', 'Systemhandbuch')
                ->where('documents.0.category_label', 'Sonstiges')
                ->where('documents.0.current_version.version_number', 1)
                ->where('documents.0.validity_status', 'expiring_soon')
                ->where('documents.0.validity_status_label', 'Läuft bald ab')
                ->where('documents.0.contract_reference', 'SUP-2026-42')
                ->where('canUploadDocuments', true)
                ->where('documentCategories.0.value', 'maintenance_contract'));
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());

        return $user;
    }
}
