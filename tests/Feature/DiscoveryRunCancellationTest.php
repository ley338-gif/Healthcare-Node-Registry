<?php

namespace Tests\Feature;

use App\Models\DiscoveryRun;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DiscoveryRunCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_running_discovery_run_can_be_cancelled(): void
    {
        $user = $this->createRunner();
        $run = DiscoveryRun::factory()->create(['status' => DiscoveryRun::STATUS_RUNNING]);

        $response = $this->actingAs($user)->post("/discovery/runs/{$run->public_id}/cancel");

        $response->assertRedirect();
        $this->assertDatabaseHas('discovery_runs', ['id' => $run->id, 'status' => DiscoveryRun::STATUS_CANCELLING]);
        $this->assertDatabaseHas('security_events', ['event_type' => 'discovery.run.cancelled']);
    }

    public function test_a_completed_run_cannot_be_cancelled_again(): void
    {
        $user = $this->createRunner();
        $run = DiscoveryRun::factory()->create(['status' => DiscoveryRun::STATUS_COMPLETED]);

        $this->actingAs($user)->post("/discovery/runs/{$run->public_id}/cancel");

        $this->assertDatabaseHas('discovery_runs', ['id' => $run->id, 'status' => DiscoveryRun::STATUS_COMPLETED]);
    }

    public function test_a_user_without_run_permission_cannot_cancel(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $run = DiscoveryRun::factory()->create(['status' => DiscoveryRun::STATUS_RUNNING]);

        $this->actingAs($user)->post("/discovery/runs/{$run->public_id}/cancel")->assertForbidden();
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
