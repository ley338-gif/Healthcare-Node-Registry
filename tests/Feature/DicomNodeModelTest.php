<?php

namespace Tests\Feature;

use App\Models\DicomNode;
use App\Models\System;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DicomNodeModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_dicom_node_can_be_assigned_to_a_system(): void
    {
        $system = System::factory()->create();

        $node = DicomNode::query()->create([
            'system_id' => $system->id,
            'name' => 'PACS Store SCP',
            'ae_title' => 'jivex_pacs',
            'host' => '10.91.19.11',
            'port' => 11112,
            'role' => 'scp',
            'status' => 'active',
            'tls_enabled' => false,
            'supports_echo' => true,
            'supports_store' => true,
            'supports_query' => true,
            'supports_retrieve' => true,
            'supports_storage_commitment' => true,
            'supports_mpps' => false,
            'supports_worklist' => false,
        ]);

        $this->assertNotNull($node->public_id);
        $this->assertSame('JIVEX_PACS', $node->ae_title);
        $this->assertSame(11112, $node->port);
        $this->assertTrue($node->supports_store);
        $this->assertTrue($node->system->is($system));
        $this->assertTrue(
            $system->dicomNodes()->whereKey($node->id)->exists(),
        );
    }

    public function test_active_scope_excludes_archived_dicom_nodes(): void
    {
        $system = System::factory()->create();

        DicomNode::factory()->create([
            'system_id' => $system->id,
            'archived_at' => null,
        ]);

        DicomNode::factory()->create([
            'system_id' => $system->id,
            'status' => 'inactive',
            'archived_at' => now(),
        ]);

        $this->assertSame(
            1,
            DicomNode::query()->active()->count(),
        );
    }

    public function test_same_endpoint_cannot_be_registered_twice_for_one_system(): void
    {
        $system = System::factory()->create();

        DicomNode::factory()->create([
            'system_id' => $system->id,
            'ae_title' => 'PACS01',
            'host' => '10.10.10.20',
            'port' => 11112,
        ]);

        $this->expectException(
            QueryException::class,
        );

        DicomNode::factory()->create([
            'system_id' => $system->id,
            'ae_title' => 'PACS01',
            'host' => '10.10.10.20',
            'port' => 11112,
        ]);
    }
}
