<?php

namespace Tests\Feature;

use App\Models\DicomConnection;
use App\Models\DicomNode;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DicomConnectionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_manager_can_create_a_dicom_connection(): void
    {
        $user = $this->createRegistryManager();

        $sourceNode = DicomNode::factory()->create([
            'ae_title' => 'CT01',
        ]);

        $targetNode = DicomNode::factory()->create([
            'ae_title' => 'PACS01',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                '/dicom-connections',
                $this->validPayload(
                    $sourceNode,
                    $targetNode,
                    [
                        'name' => 'CT Bildversand',
                        'service' => DicomConnection::SERVICE_STORE,
                        'calling_ae_title' => 'ct_store',
                        'called_ae_title' => 'pacs_store',
                        'port_override' => 11112,
                    ],
                ),
            );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('dicom_connections', [
            'source_dicom_node_id' => $sourceNode->id,
            'target_dicom_node_id' => $targetNode->id,
            'name' => 'CT Bildversand',
            'service' => DicomConnection::SERVICE_STORE,
            'calling_ae_title' => 'CT_STORE',
            'called_ae_title' => 'PACS_STORE',
            'port_override' => 11112,
            'status' => 'active',
            'archived_at' => null,
        ]);

        $this->assertDatabaseHas('security_events', [
            'event_type' => 'registry.dicom_connection.created',
            'subject_type' => DicomConnection::class,
        ]);
    }

    public function test_source_and_target_node_cannot_be_identical(): void
    {
        $user = $this->createRegistryManager();
        $node = DicomNode::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(
                '/dicom-connections',
                $this->validPayload(
                    $node,
                    $node,
                ),
            );

        $response->assertSessionHasErrors(
            'target_dicom_node_id',
        );

        $this->assertDatabaseCount(
            'dicom_connections',
            0,
        );
    }

    public function test_duplicate_service_path_is_rejected(): void
    {
        $user = $this->createRegistryManager();

        $sourceNode = DicomNode::factory()->create();
        $targetNode = DicomNode::factory()->create();

        DicomConnection::factory()->create([
            'source_dicom_node_id' => $sourceNode->id,
            'target_dicom_node_id' => $targetNode->id,
            'service' => DicomConnection::SERVICE_STORE,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                '/dicom-connections',
                $this->validPayload(
                    $sourceNode,
                    $targetNode,
                    [
                        'service' => DicomConnection::SERVICE_STORE,
                    ],
                ),
            );

        $response->assertSessionHasErrors('service');

        $this->assertDatabaseCount(
            'dicom_connections',
            1,
        );
    }

    public function test_move_connection_requires_a_destination_node(): void
    {
        $user = $this->createRegistryManager();

        $sourceNode = DicomNode::factory()->create();
        $targetNode = DicomNode::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(
                '/dicom-connections',
                $this->validPayload(
                    $sourceNode,
                    $targetNode,
                    [
                        'service' => DicomConnection::SERVICE_MOVE,
                        'destination_dicom_node_id' => null,
                    ],
                ),
            );

        $response->assertSessionHasErrors(
            'destination_dicom_node_id',
        );

        $this->assertDatabaseCount(
            'dicom_connections',
            0,
        );
    }

    public function test_non_move_connection_rejects_a_destination_node(): void
    {
        $user = $this->createRegistryManager();

        $sourceNode = DicomNode::factory()->create();
        $targetNode = DicomNode::factory()->create();
        $destinationNode = DicomNode::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(
                '/dicom-connections',
                $this->validPayload(
                    $sourceNode,
                    $targetNode,
                    [
                        'service' => DicomConnection::SERVICE_STORE,
                        'destination_dicom_node_id' => $destinationNode->id,
                    ],
                ),
            );

        $response->assertSessionHasErrors(
            'destination_dicom_node_id',
        );

        $this->assertDatabaseCount(
            'dicom_connections',
            0,
        );
    }

    public function test_registry_manager_can_create_a_move_connection(): void
    {
        $user = $this->createRegistryManager();

        $sourceNode = DicomNode::factory()->create();
        $targetNode = DicomNode::factory()->create();
        $destinationNode = DicomNode::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(
                '/dicom-connections',
                $this->validPayload(
                    $sourceNode,
                    $targetNode,
                    [
                        'service' => DicomConnection::SERVICE_MOVE,
                        'destination_dicom_node_id' => $destinationNode->id,
                    ],
                ),
            );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('dicom_connections', [
            'source_dicom_node_id' => $sourceNode->id,
            'target_dicom_node_id' => $targetNode->id,
            'destination_dicom_node_id' => $destinationNode->id,
            'service' => DicomConnection::SERVICE_MOVE,
        ]);
    }

    public function test_registry_manager_can_update_a_dicom_connection(): void
    {
        $user = $this->createRegistryManager();

        $sourceNode = DicomNode::factory()->create();
        $targetNode = DicomNode::factory()->create();

        $connection = DicomConnection::factory()->create([
            'source_dicom_node_id' => $sourceNode->id,
            'target_dicom_node_id' => $targetNode->id,
            'name' => 'Alte Verbindung',
            'service' => DicomConnection::SERVICE_ECHO,
            'calling_ae_title' => null,
            'called_ae_title' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                "/dicom-connections/{$connection->public_id}",
                $this->validPayload(
                    $sourceNode,
                    $targetNode,
                    [
                        'name' => 'PACS Query',
                        'service' => DicomConnection::SERVICE_QUERY,
                        'calling_ae_title' => 'viewer_query',
                        'called_ae_title' => 'pacs_query',
                        'test_enabled' => false,
                    ],
                ),
            );

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('dicom_connections', [
            'id' => $connection->id,
            'name' => 'PACS Query',
            'service' => DicomConnection::SERVICE_QUERY,
            'calling_ae_title' => 'VIEWER_QUERY',
            'called_ae_title' => 'PACS_QUERY',
            'test_enabled' => false,
        ]);

        $this->assertDatabaseHas('security_events', [
            'event_type' => 'registry.dicom_connection.updated',
            'subject_type' => DicomConnection::class,
        ]);
    }

    public function test_registry_manager_can_archive_a_dicom_connection(): void
    {
        $user = $this->createRegistryManager();

        $connection = DicomConnection::factory()->create([
            'status' => 'active',
            'test_enabled' => true,
            'archived_at' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                "/dicom-connections/{$connection->public_id}/archive",
            );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $connection->refresh();

        $this->assertNotNull($connection->archived_at);
        $this->assertSame('inactive', $connection->status);
        $this->assertFalse($connection->test_enabled);

        $this->assertDatabaseHas('security_events', [
            'event_type' => 'registry.dicom_connection.archived',
            'subject_type' => DicomConnection::class,
        ]);
    }

    public function test_unprivileged_user_cannot_create_a_connection(): void
    {
        $user = User::factory()->create();

        $sourceNode = DicomNode::factory()->create();
        $targetNode = DicomNode::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(
                '/dicom-connections',
                $this->validPayload(
                    $sourceNode,
                    $targetNode,
                ),
            );

        $response->assertForbidden();

        $this->assertDatabaseCount(
            'dicom_connections',
            0,
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(
        DicomNode $sourceNode,
        DicomNode $targetNode,
        array $overrides = [],
    ): array {
        return array_merge([
            'source_dicom_node_id' => $sourceNode->id,
            'target_dicom_node_id' => $targetNode->id,
            'destination_dicom_node_id' => null,
            'name' => 'DICOM-Verbindung',
            'service' => DicomConnection::SERVICE_ECHO,
            'status' => 'active',
            'evidence_status' => 'manually_documented',
            'calling_ae_title' => null,
            'called_ae_title' => null,
            'port_override' => null,
            'tls_enabled' => false,
            'test_enabled' => true,
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
