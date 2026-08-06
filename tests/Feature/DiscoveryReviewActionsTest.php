<?php

namespace Tests\Feature;

use App\Models\DiscoveredHost;
use App\Models\DiscoveryRun;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Discovery\DiscoveryEchoCommandResult;
use App\Services\Discovery\DiscoveryEchoCommandRunner;
use App\Services\Discovery\Probes\PortProbeResult;
use App\Services\Discovery\Probes\TcpPortProbe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DiscoveryReviewActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_finding_can_be_confirmed(): void
    {
        $user = $this->createReviewer();
        $host = DiscoveredHost::factory()->create(['status' => DiscoveredHost::STATUS_DISCOVERED]);

        $response = $this->actingAs($user)->post("/discovery/hosts/{$host->public_id}/confirm");

        $response->assertRedirect();
        $this->assertDatabaseHas('discovered_hosts', ['id' => $host->id, 'status' => DiscoveredHost::STATUS_CONFIRMED]);
        $this->assertDatabaseHas('security_events', ['event_type' => 'discovery.finding.confirmed']);
    }

    public function test_a_finding_can_be_ignored(): void
    {
        $user = $this->createReviewer();
        $host = DiscoveredHost::factory()->create(['status' => DiscoveredHost::STATUS_DISCOVERED]);

        $response = $this->actingAs($user)->post("/discovery/hosts/{$host->public_id}/ignore");

        $response->assertRedirect();
        $this->assertDatabaseHas('discovered_hosts', ['id' => $host->id, 'status' => DiscoveredHost::STATUS_IGNORED]);
        $this->assertDatabaseHas('security_events', ['event_type' => 'discovery.finding.ignored']);
    }

    public function test_a_user_without_manage_permission_cannot_review(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $host = DiscoveredHost::factory()->create();

        $this->actingAs($user)->post("/discovery/hosts/{$host->public_id}/confirm")->assertForbidden();
    }

    public function test_retest_updates_ports_and_classification_using_mocked_probes(): void
    {
        $user = $this->createReviewer();
        $run = DiscoveryRun::factory()->create();
        $host = DiscoveredHost::factory()->create(['discovery_run_id' => $run->id, 'ip_address' => '192.168.20.10', 'hostname' => 'pacs-archive-01']);
        $host->ports()->create(['port' => 11112, 'protocol' => 'tcp', 'is_open' => false, 'is_dicom_candidate' => true]);

        $this->app->bind(TcpPortProbe::class, fn () => new class implements TcpPortProbe
        {
            public function scanMany(array $targets, int $timeoutSeconds): array
            {
                $results = [];
                foreach ($targets as $ip => $ports) {
                    foreach ($ports as $port) {
                        $results[$ip][$port] = new PortProbeResult(true, 12);
                    }
                }

                return $results;
            }
        });

        $this->app->bind(DiscoveryEchoCommandRunner::class, fn () => new class implements DiscoveryEchoCommandRunner
        {
            public function runMany(array $targets, int $timeoutSeconds): array
            {
                $results = [];
                foreach ($targets as $target) {
                    $results[$target->key()] = new DiscoveryEchoCommandResult($target, true, true, null, null, 'C-ECHO ok', 8);
                }

                return $results;
            }
        });

        $response = $this->actingAs($user)->post("/discovery/hosts/{$host->public_id}/retest");

        $response->assertRedirect();
        $this->assertDatabaseHas('discovered_ports', ['discovered_host_id' => $host->id, 'port' => 11112, 'is_open' => true]);
        $this->assertDatabaseHas('dicom_discovery_results', ['discovered_host_id' => $host->id, 'port' => 11112, 'echo_successful' => true]);
        $host->refresh();
        self::assertSame('pacs', $host->suggested_system_type);
        self::assertGreaterThan(0, $host->confidence_percentage);
    }

    private function createReviewer(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $ids = Permission::query()->whereIn('name', ['discovery.manage'])->pluck('id');
        $role = Role::query()->create(['name' => 'discovery-manager', 'display_name' => 'Discovery Manager']);
        $role->permissions()->sync($ids);
        $user->roles()->attach($role);

        return $user;
    }
}
