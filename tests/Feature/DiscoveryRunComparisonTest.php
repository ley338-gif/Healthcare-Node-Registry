<?php

namespace Tests\Feature;

use App\Models\DicomDiscoveryResult;
use App\Models\DiscoveredHost;
use App\Models\DiscoveredPort;
use App\Models\DiscoveryRun;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class DiscoveryRunComparisonTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_can_compare_two_completed_runs_of_the_same_target_range(): void
    {
        $baseline = $this->completedRun('Ausgangslauf', now()->subDay());
        $current = $this->completedRun('Aktueller Lauf', now());

        $changedBefore = $this->host($baseline, '10.20.30.1', ['hostname' => 'pacs-alt', 'confidence_score' => DiscoveredHost::CONFIDENCE_MEDIUM, 'confidence_percentage' => 50]);
        DiscoveredPort::factory()->create(['discovered_host_id' => $changedBefore->id, 'port' => 104, 'is_open' => true]);
        DicomDiscoveryResult::factory()->create(['discovered_host_id' => $changedBefore->id, 'port' => 104, 'called_ae' => 'PACS_ALT', 'echo_successful' => true]);

        $changedAfter = $this->host($current, '10.20.30.1', ['hostname' => 'pacs-neu', 'confidence_score' => DiscoveredHost::CONFIDENCE_HIGH, 'confidence_percentage' => 85]);
        DiscoveredPort::factory()->create(['discovered_host_id' => $changedAfter->id, 'port' => 11112, 'is_open' => true]);
        DicomDiscoveryResult::factory()->create(['discovered_host_id' => $changedAfter->id, 'port' => 11112, 'called_ae' => 'PACS_NEU', 'echo_successful' => true]);

        $this->host($current, '10.20.30.2', ['hostname' => 'neuer-host']);
        $this->host($baseline, '10.20.30.3', ['hostname' => 'alter-host']);
        $this->host($baseline, '10.20.30.4', ['hostname' => 'unveraendert']);
        $this->host($current, '10.20.30.4', ['hostname' => 'unveraendert']);

        $response = $this->actingAs($this->viewer())
            ->get("/discovery/runs/{$current->public_id}/compare/{$baseline->public_id}");

        $response->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Discovery/Runs/Compare')
            ->where('baselineRun.public_id', $baseline->public_id)
            ->where('currentRun.public_id', $current->public_id)
            ->where('summary.new', 1)
            ->where('summary.missing', 1)
            ->where('summary.changed', 1)
            ->where('summary.unchanged', 1)
            ->where('summary.total', 4)
            ->has('results', 4)
            ->where('results.0.change_type', 'changed')
            ->where('results.0.ip_address', '10.20.30.1')
            ->where('results.0.changes.0.field', 'hostname'));
    }

    public function test_only_older_completed_runs_of_the_same_range_are_offered_for_comparison(): void
    {
        $baseline = $this->completedRun('Vergleichbar', now()->subDay());
        $current = $this->completedRun('Aktuell', now());
        $this->completedRun('Anderer Bereich', now()->subDays(2), '10.99.0.0/24');
        DiscoveryRun::factory()->create(['name' => 'Noch aktiv', 'status' => DiscoveryRun::STATUS_RUNNING, 'ip_range' => $current->ip_range]);

        $this->actingAs($this->viewer())
            ->get("/discovery/runs/{$current->public_id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('comparisonRuns', 1)
                ->where('comparisonRuns.0.public_id', $baseline->public_id));
    }

    public function test_comparison_rejects_incomplete_or_different_target_ranges(): void
    {
        $viewer = $this->viewer();
        $baseline = $this->completedRun('Ausgangslauf', now()->subDay());
        $running = DiscoveryRun::factory()->create(['status' => DiscoveryRun::STATUS_RUNNING, 'ip_range' => $baseline->ip_range]);
        $differentRange = $this->completedRun('Anderer Bereich', now(), '10.99.0.0/24');

        $this->actingAs($viewer)->get("/discovery/runs/{$running->public_id}/compare/{$baseline->public_id}")->assertUnprocessable();
        $this->actingAs($viewer)->get("/discovery/runs/{$differentRange->public_id}/compare/{$baseline->public_id}")->assertUnprocessable();
    }

    public function test_comparison_requires_discovery_view_permission(): void
    {
        $this->seed();
        $baseline = $this->completedRun('Ausgangslauf', now()->subDay());
        $current = $this->completedRun('Aktueller Lauf', now());

        $this->actingAs(User::factory()->create())
            ->get("/discovery/runs/{$current->public_id}/compare/{$baseline->public_id}")
            ->assertForbidden();
    }

    private function completedRun(string $name, mixed $finishedAt, string $range = '10.20.30.0/24'): DiscoveryRun
    {
        return DiscoveryRun::factory()->create([
            'name' => $name,
            'status' => DiscoveryRun::STATUS_COMPLETED,
            'ip_range' => $range,
            'finished_at' => $finishedAt,
        ]);
    }

    /** @param array<string, mixed> $attributes */
    private function host(DiscoveryRun $run, string $ipAddress, array $attributes = []): DiscoveredHost
    {
        return DiscoveredHost::factory()->create(array_merge([
            'discovery_run_id' => $run->id,
            'ip_address' => $ipAddress,
            'hostname' => null,
            'reverse_dns' => null,
            'is_reachable' => true,
            'confidence_score' => DiscoveredHost::CONFIDENCE_UNKNOWN,
            'confidence_percentage' => 0,
            'suggested_system_type' => null,
        ], $attributes));
    }

    private function viewer(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $role = Role::query()->create(['name' => 'comparison-viewer-'.uniqid(), 'display_name' => 'Comparison Viewer']);
        $role->permissions()->attach(Permission::query()->where('name', 'discovery.view')->firstOrFail());
        $user->roles()->attach($role);

        return $user;
    }
}
