<?php

namespace Tests\Feature;

use App\Jobs\RunDiscoveryScanJob;
use App\Models\DiscoveryAllowedNetwork;
use App\Models\DiscoveryRun;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class DiscoveryRunCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_discovery_run_is_created_and_queued(): void
    {
        Queue::fake();
        $user = $this->createRunner();

        $response = $this->actingAs($user)->post('/discovery/runs', $this->validPayload());

        $response->assertSessionHasNoErrors();
        $run = DiscoveryRun::query()->firstOrFail();
        $response->assertRedirect("/discovery/runs/{$run->public_id}");

        $this->assertDatabaseHas('discovery_runs', [
            'id' => $run->id,
            'name' => 'Radiologie Hauptklinik',
            'ip_range' => '192.168.20.0/29',
            'status' => DiscoveryRun::STATUS_PENDING,
            'total_ips' => 8,
        ]);
        $this->assertDatabaseCount('discovery_ports', 1);
        $this->assertDatabaseCount('discovery_ae_candidates', 2);
        $this->assertDatabaseHas('security_events', ['event_type' => 'discovery.run.started']);

        Queue::assertPushedOn('discovery', RunDiscoveryScanJob::class);
    }

    public function test_a_public_ip_range_is_rejected(): void
    {
        Queue::fake();
        $user = $this->createRunner();

        $response = $this->actingAs($user)->post('/discovery/runs', $this->validPayload(['ip_range' => '8.8.8.0/29']));

        $response->assertSessionHasErrors(['ip_range']);
        $this->assertDatabaseCount('discovery_runs', 0);
    }

    public function test_target_range_must_be_fully_covered_by_an_active_allowed_network(): void
    {
        Queue::fake();
        $user = $this->createRunner();
        DiscoveryAllowedNetwork::query()->delete();
        DiscoveryAllowedNetwork::query()->create([
            'cidr' => '10.20.0.0/24',
            'description' => 'Explicitly authorized test network',
            'active' => true,
        ]);

        $this->actingAs($user)->post('/discovery/runs', $this->validPayload([
            'ip_range' => '10.20.0.248/29',
        ]))->assertSessionHasNoErrors();

        $this->actingAs($user)->post('/discovery/runs', $this->validPayload([
            'name' => 'Outside authorization',
            'ip_range' => '10.20.1.0/29',
        ]))->assertSessionHasErrors(['ip_range']);

        self::assertSame(1, DiscoveryRun::query()->count());
    }

    public function test_a_range_exceeding_the_configured_maximum_is_rejected(): void
    {
        Queue::fake();
        $user = $this->createRunner();

        $response = $this->actingAs($user)->post('/discovery/runs', $this->validPayload(['ip_range' => '10.0.0.0/8']));

        $response->assertSessionHasErrors(['ip_range']);
    }

    public function test_confirmation_checkbox_is_required(): void
    {
        Queue::fake();
        $user = $this->createRunner();

        $response = $this->actingAs($user)->post('/discovery/runs', $this->validPayload(['confirmed' => false]));

        $response->assertSessionHasErrors(['confirmed']);
    }

    public function test_at_least_one_enabled_port_is_required(): void
    {
        Queue::fake();
        $user = $this->createRunner();

        $response = $this->actingAs($user)->post('/discovery/runs', $this->validPayload([
            'ports' => [['port' => 11112, 'protocol' => 'tcp', 'label' => 'DICOM', 'is_dicom_candidate' => true, 'enabled' => false]],
        ]));

        $response->assertSessionHasErrors(['ports']);
    }

    /** @return array<string, mixed> */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Radiologie Hauptklinik',
            'location' => 'Hauptklinik',
            'department' => 'Radiologie',
            'ip_range' => '192.168.20.0/29',
            'exclude_ips' => [],
            'description' => null,
            'scan_options' => [
                'ping_enabled' => true,
                'reverse_dns_enabled' => true,
                'tcp_scan_enabled' => true,
                'dicom_check_enabled' => true,
                'scan_unresponsive_hosts' => false,
                'max_parallel_hosts' => 4,
                'timeout_seconds' => 2,
                'retries' => 1,
                'profile' => 'standard',
            ],
            'ports' => [
                ['port' => 11112, 'protocol' => 'tcp', 'label' => 'DICOM', 'is_dicom_candidate' => true, 'enabled' => true],
            ],
            'ae_candidates' => [
                ['ae_title' => 'HNR_DISCOVERY', 'role' => 'calling', 'source' => 'default'],
                ['ae_title' => 'PACS01', 'role' => 'called', 'source' => 'manual'],
            ],
            'confirmed' => true,
        ], $overrides);
    }

    private function createRunner(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $ids = Permission::query()->whereIn('name', ['discovery.run'])->pluck('id');
        $role = Role::query()->create(['name' => 'discovery-runner', 'display_name' => 'Discovery Runner']);
        $role->permissions()->sync($ids);
        $user->roles()->attach($role);

        return $user;
    }
}
