<?php

namespace Tests\Feature;

use App\Models\DicomConnection;
use App\Models\DicomNode;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\DiagnosticPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class GlobalDicomConnectionOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_list_uses_existing_connections_and_enforces_view_permission(): void
    {
        $connection = DicomConnection::factory()->create(['name' => 'CT Store']);

        $this->actingAs(User::factory()->create())->get('/connections')->assertForbidden();

        $this->actingAs($this->manager())->get('/connections')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Connections/Index')
                ->where('connections.total', 1)
                ->where('connections.data.0.public_id', $connection->public_id));
    }

    public function test_global_filters_cover_system_service_port_and_ae_title(): void
    {
        $matching = DicomConnection::factory()->create([
            'service' => DicomConnection::SERVICE_STORE,
            'port_override' => 11112,
            'calling_ae_title' => 'CT_CALLING',
        ]);
        DicomConnection::factory()->create(['service' => DicomConnection::SERVICE_ECHO]);

        $sourceSystem = $matching->sourceNode->system;
        $targetSystem = $matching->targetNode->system;

        $this->actingAs($this->manager())->get('/connections?'.http_build_query([
            'source_system' => $sourceSystem->public_id,
            'target_system' => $targetSystem->public_id,
            'service' => 'store',
            'port' => 11112,
            'ae_title' => 'CT_CALL',
        ]))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('connections.total', 1)
            ->where('connections.data.0.public_id', $matching->public_id));
    }

    public function test_global_list_exposes_only_authorized_diagnostic_services(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $role = Role::query()->create(['name' => 'move-operator', 'display_name' => 'Move Operator']);
        $role->permissions()->attach(Permission::query()->whereIn('name', [
            'registry.view',
            DiagnosticPermission::Move->value,
        ])->pluck('id'));
        $user->roles()->attach($role);

        $this->actingAs($user)->get('/connections')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('runnableServices', ['move']));
    }

    public function test_creation_and_update_are_immediately_visible_in_global_and_system_views(): void
    {
        $manager = $this->manager();
        $source = DicomNode::factory()->create();
        $target = DicomNode::factory()->create();

        $this->actingAs($manager)->post('/dicom-connections', $this->payload($source, $target, 'Neue Verbindung'))->assertSessionHasNoErrors();
        $connection = DicomConnection::query()->where('name', 'Neue Verbindung')->firstOrFail();

        $this->actingAs($manager)->get('/connections')->assertInertia(fn (Assert $page) => $page->where('connections.data.0.public_id', $connection->public_id));
        $this->actingAs($manager)->get('/systems/'.$source->system->public_id)->assertInertia(fn (Assert $page) => $page->where('dicomConnections.0.public_id', $connection->public_id));

        $this->actingAs($manager)->put('/dicom-connections/'.$connection->public_id, $this->payload($source, $target, 'Geänderte Verbindung'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('dicom_connections', ['id' => $connection->id, 'name' => 'Geänderte Verbindung']);
        $this->assertDatabaseHas('security_events', ['event_type' => 'registry.dicom_connection.updated', 'subject_type' => DicomConnection::class]);
    }

    public function test_archived_connection_and_test_prefill_are_available(): void
    {
        $manager = $this->manager();
        $connection = DicomConnection::factory()->create(['service' => 'store']);
        $this->actingAs($manager)->post('/dicom-connections/'.$connection->public_id.'/archive')->assertRedirect();
        $this->actingAs($manager)->get('/connections?status=archived')->assertInertia(fn (Assert $page) => $page->where('connections.total', 1));

        $this->actingAs($manager)->get('/tests?'.http_build_query([
            'node' => $connection->targetNode->public_id,
            'service' => 'store',
            'calling_ae_title' => 'CALLING',
            'called_ae_title' => 'CALLED',
        ]))->assertInertia(fn (Assert $page) => $page
            ->where('connectionPrefill.node', $connection->targetNode->public_id)
            ->where('connectionPrefill.service', 'store')
            ->where('connectionPrefill.calling_ae_title', 'CALLING')
            ->where('connectionPrefill.called_ae_title', 'CALLED'));
    }

    public function test_duplicate_reuses_the_existing_entity_and_is_audited(): void
    {
        $connection = DicomConnection::factory()->create(['service' => 'store']);

        $this->actingAs($this->manager())
            ->post('/dicom-connections/'.$connection->public_id.'/duplicate')
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('dicom_connections', 2);
        $this->assertDatabaseHas('security_events', [
            'event_type' => 'registry.dicom_connection.duplicated',
            'subject_type' => DicomConnection::class,
        ]);
    }

    private function manager(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());

        return $user;
    }

    /** @return array<string, mixed> */
    private function payload(DicomNode $source, DicomNode $target, string $name): array
    {
        return ['source_dicom_node_id' => $source->id, 'target_dicom_node_id' => $target->id, 'destination_dicom_node_id' => null, 'name' => $name, 'service' => 'echo', 'status' => 'active', 'evidence_status' => 'manually_documented', 'calling_ae_title' => null, 'called_ae_title' => null, 'port_override' => null, 'tls_enabled' => false, 'test_enabled' => true, 'description' => null, 'notes' => null];
    }
}
