<?php

namespace Tests\Feature;

use App\Models\DicomConnection;
use App\Models\DicomNode;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

final class DicomNetworkMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_network_map(): void
    {
        $user = $this->createRegistryManager();

        $sourceNode = DicomNode::factory()->create([
            'name' => 'CT',
            'ae_title' => 'CT01',
        ]);

        $targetNode = DicomNode::factory()->create([
            'name' => 'PACS Store',
            'ae_title' => 'PACS_STORE',
        ]);

        DicomConnection::factory()->create([
            'source_dicom_node_id' => $sourceNode->id,
            'target_dicom_node_id' => $targetNode->id,
            'name' => 'CT Bildversand',
            'service' => DicomConnection::SERVICE_STORE,
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/network');

        $response->assertOk();

        $response->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('Network/Index')
                ->has('nodes', 2)
                ->has('connections', 1)
                ->where('summary.nodes', 2)
                ->where('summary.connections', 1)
                ->where(
                    'connections.0.service',
                    DicomConnection::SERVICE_STORE,
                ),
        );
    }

    public function test_guest_cannot_view_network_map(): void
    {
        $this
            ->get('/network')
            ->assertRedirect();
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
