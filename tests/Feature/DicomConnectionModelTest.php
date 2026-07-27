<?php

namespace Tests\Feature;

use App\Models\DicomConnection;
use App\Models\DicomNode;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DicomConnectionModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_dicom_connection_links_source_and_target_nodes(): void
    {
        $sourceNode = DicomNode::factory()->create([
            'name' => 'CT 1',
            'ae_title' => 'CT01',
        ]);

        $targetNode = DicomNode::factory()->create([
            'name' => 'PACS',
            'ae_title' => 'PACS01',
        ]);

        $connection = DicomConnection::query()->create([
            'source_dicom_node_id' => $sourceNode->id,
            'target_dicom_node_id' => $targetNode->id,
            'name' => 'CT Bildversand',
            'service' => DicomConnection::SERVICE_STORE,
            'status' => 'active',
            'calling_ae_title' => 'ct_store',
            'called_ae_title' => 'pacs_store',
            'port_override' => 11112,
            'tls_enabled' => false,
            'test_enabled' => true,
        ]);

        $this->assertNotNull($connection->public_id);

        $this->assertSame(
            'CT_STORE',
            $connection->calling_ae_title,
        );

        $this->assertSame(
            'PACS_STORE',
            $connection->called_ae_title,
        );

        $this->assertTrue(
            $connection->sourceNode->is($sourceNode),
        );

        $this->assertTrue(
            $connection->targetNode->is($targetNode),
        );

        $this->assertTrue(
            $sourceNode
                ->outgoingConnections()
                ->whereKey($connection->id)
                ->exists(),
        );

        $this->assertTrue(
            $targetNode
                ->incomingConnections()
                ->whereKey($connection->id)
                ->exists(),
        );
    }

    public function test_move_connection_can_have_a_separate_destination(): void
    {
        $requester = DicomNode::factory()->create();
        $queryRetrieveScp = DicomNode::factory()->create();
        $destination = DicomNode::factory()->create();

        $connection = DicomConnection::factory()->create([
            'source_dicom_node_id' => $requester->id,
            'target_dicom_node_id' => $queryRetrieveScp->id,
            'destination_dicom_node_id' => $destination->id,
            'service' => DicomConnection::SERVICE_MOVE,
        ]);

        $this->assertTrue(
            $connection->destinationNode->is($destination),
        );

        $this->assertTrue(
            $destination
                ->moveDestinationConnections()
                ->whereKey($connection->id)
                ->exists(),
        );
    }

    public function test_active_scope_excludes_archived_connections(): void
    {
        DicomConnection::factory()->create([
            'archived_at' => null,
        ]);

        DicomConnection::factory()->create([
            'status' => 'inactive',
            'archived_at' => now(),
        ]);

        $this->assertSame(
            1,
            DicomConnection::query()->active()->count(),
        );
    }

    public function test_duplicate_service_path_is_rejected(): void
    {
        $sourceNode = DicomNode::factory()->create();
        $targetNode = DicomNode::factory()->create();

        DicomConnection::factory()->create([
            'source_dicom_node_id' => $sourceNode->id,
            'target_dicom_node_id' => $targetNode->id,
            'service' => DicomConnection::SERVICE_STORE,
        ]);

        $this->expectException(
            QueryException::class,
        );

        DicomConnection::factory()->create([
            'source_dicom_node_id' => $sourceNode->id,
            'target_dicom_node_id' => $targetNode->id,
            'service' => DicomConnection::SERVICE_STORE,
        ]);
    }
}
