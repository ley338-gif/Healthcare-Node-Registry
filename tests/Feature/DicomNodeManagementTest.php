<?php

namespace Tests\Feature;

use App\Models\DicomNode;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DicomNodeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_manager_can_create_a_dicom_node(): void
    {
        $user = $this->createRegistryManager();
        $system = System::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(
                "/systems/{$system->public_id}/dicom-nodes",
                $this->validPayload([
                    'name' => 'VISUS PACS Store',
                    'ae_title' => 'jivex_store',
                    'host' => '10.91.19.11',
                    'port' => 11112,
                    'role' => 'scp',
                    'supports_store' => true,
                ]),
            );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('dicom_nodes', [
            'system_id' => $system->id,
            'name' => 'VISUS PACS Store',
            'ae_title' => 'JIVEX_STORE',
            'host' => '10.91.19.11',
            'port' => 11112,
            'role' => 'scp',
            'supports_store' => true,
            'archived_at' => null,
        ]);

        $this->assertDatabaseHas('security_events', [
            'event_type' => 'registry.dicom_node.created',
            'subject_type' => DicomNode::class,
        ]);
    }

    public function test_modality_is_stored_and_normalized_to_uppercase(): void
    {
        $user = $this->createRegistryManager();
        $system = System::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(
                "/systems/{$system->public_id}/dicom-nodes",
                $this->validPayload(['modality' => 'dx']),
            );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('dicom_nodes', [
            'system_id' => $system->id,
            'modality' => 'DX',
        ]);
    }

    public function test_dicom_node_can_be_created_without_a_modality(): void
    {
        $user = $this->createRegistryManager();
        $system = System::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(
                "/systems/{$system->public_id}/dicom-nodes",
                $this->validPayload(),
            );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('dicom_nodes', [
            'system_id' => $system->id,
            'modality' => null,
        ]);
    }

    public function test_invalid_modality_is_rejected(): void
    {
        $user = $this->createRegistryManager();
        $system = System::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(
                "/systems/{$system->public_id}/dicom-nodes",
                $this->validPayload(['modality' => 'in valid!']),
            );

        $response->assertSessionHasErrors(['modality']);

        $this->assertDatabaseCount('dicom_nodes', 0);
    }

    public function test_invalid_dicom_node_data_is_rejected(): void
    {
        $user = $this->createRegistryManager();
        $system = System::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(
                "/systems/{$system->public_id}/dicom-nodes",
                $this->validPayload([
                    'name' => '',
                    'ae_title' => 'INVALID@AE',
                    'host' => '',
                    'port' => 70000,
                    'role' => 'invalid',
                ]),
            );

        $response->assertSessionHasErrors([
            'name',
            'ae_title',
            'host',
            'port',
            'role',
        ]);

        $this->assertDatabaseCount('dicom_nodes', 0);
    }

    public function test_duplicate_endpoint_is_rejected(): void
    {
        $user = $this->createRegistryManager();
        $system = System::factory()->create();

        DicomNode::factory()->create([
            'system_id' => $system->id,
            'ae_title' => 'PACS01',
            'host' => '10.10.10.20',
            'port' => 11112,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                "/systems/{$system->public_id}/dicom-nodes",
                $this->validPayload([
                    'ae_title' => 'PACS01',
                    'host' => '10.10.10.20',
                    'port' => 11112,
                ]),
            );

        $response->assertSessionHasErrors('ae_title');

        $this->assertDatabaseCount('dicom_nodes', 1);
    }

    public function test_registry_manager_can_update_a_dicom_node(): void
    {
        $user = $this->createRegistryManager();

        $dicomNode = DicomNode::factory()->create([
            'name' => 'Alte Konfiguration',
            'ae_title' => 'PACS_OLD',
            'host' => '10.10.10.20',
            'port' => 104,
            'role' => 'scp',
            'supports_store' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                "/dicom-nodes/{$dicomNode->public_id}",
                $this->validPayload([
                    'name' => 'PACS Produktion',
                    'ae_title' => 'pacs_prod',
                    'host' => '10.10.10.21',
                    'port' => 11112,
                    'role' => 'both',
                    'supports_store' => true,
                    'supports_query' => true,
                    'supports_retrieve' => true,
                ]),
            );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('dicom_nodes', [
            'id' => $dicomNode->id,
            'name' => 'PACS Produktion',
            'ae_title' => 'PACS_PROD',
            'host' => '10.10.10.21',
            'port' => 11112,
            'role' => 'both',
            'supports_store' => true,
            'supports_query' => true,
            'supports_retrieve' => true,
        ]);

        $this->assertDatabaseHas('security_events', [
            'event_type' => 'registry.dicom_node.updated',
            'subject_type' => DicomNode::class,
        ]);
    }

    public function test_registry_manager_can_update_the_modality_of_a_dicom_node(): void
    {
        $user = $this->createRegistryManager();
        $dicomNode = DicomNode::factory()->create(['modality' => 'CT']);

        $response = $this
            ->actingAs($user)
            ->put(
                "/dicom-nodes/{$dicomNode->public_id}",
                $this->validPayload([
                    'name' => $dicomNode->name,
                    'ae_title' => $dicomNode->ae_title,
                    'host' => $dicomNode->host,
                    'port' => $dicomNode->port,
                    'modality' => 'dx',
                ]),
            );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('dicom_nodes', [
            'id' => $dicomNode->id,
            'modality' => 'DX',
        ]);
    }

    public function test_registry_manager_can_archive_a_dicom_node(): void
    {
        $user = $this->createRegistryManager();
        $dicomNode = DicomNode::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(
                "/dicom-nodes/{$dicomNode->public_id}/archive",
            );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $dicomNode->refresh();

        $this->assertNotNull($dicomNode->archived_at);
        $this->assertSame('inactive', $dicomNode->status);

        $this->assertDatabaseHas('security_events', [
            'event_type' => 'registry.dicom_node.archived',
            'subject_type' => DicomNode::class,
        ]);
    }

    public function test_unprivileged_user_cannot_create_a_dicom_node(): void
    {
        $user = User::factory()->create();
        $system = System::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(
                "/systems/{$system->public_id}/dicom-nodes",
                $this->validPayload(),
            );

        $response->assertForbidden();

        $this->assertDatabaseCount('dicom_nodes', 0);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'PACS DICOM',
            'ae_title' => 'PACS01',
            'host' => '10.10.10.20',
            'port' => 11112,
            'role' => 'both',
            'status' => 'active',
            'tls_enabled' => false,
            'supports_echo' => true,
            'supports_store' => false,
            'supports_query' => false,
            'supports_retrieve' => false,
            'supports_storage_commitment' => false,
            'supports_mpps' => false,
            'supports_worklist' => false,
            'description' => null,
            'notes' => null,
        ], $overrides);
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
