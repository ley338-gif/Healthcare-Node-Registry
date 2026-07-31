<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DiagnosticTestRun;
use App\Models\DicomNode;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_registry_counts(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $o = Organization::query()->create(['name' => 'Testorganisation']);
        $s = Site::query()->create(['organization_id' => $o->id, 'name' => 'Teststandort', 'country_code' => 'DE', 'timezone' => 'Europe/Berlin']);
        Department::query()->create(['site_id' => $s->id, 'name' => 'Testabteilung']);
        $this->actingAs($user)->get('/')->assertOk()->assertInertia(fn ($page) => $page->component('Dashboard')->where('summary.organizations', 1)->where('summary.sites', 1)->where('summary.departments', 1)->where('summary.systems', 0));
    }

    public function test_authorized_user_sees_diagnostic_status_without_duplicating_node_counts(): void
    {
        $this->withoutVite();
        $this->seed();
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());
        $node = DicomNode::factory()->create();
        DiagnosticTestRun::factory()->create(['dicom_node_id' => $node->id, 'system_id' => $node->system_id, 'test_type' => 'dicom_echo', 'status' => 'success', 'duration_ms' => 100, 'finished_at' => now()->subMinutes(2)]);
        DiagnosticTestRun::factory()->create(['dicom_node_id' => $node->id, 'system_id' => $node->system_id, 'status' => 'failed', 'duration_ms' => 300]);
        DiagnosticTestRun::factory()->create(['dicom_node_id' => $node->id, 'system_id' => $node->system_id, 'status' => 'timeout', 'duration_ms' => 500]);

        $this->actingAs($user)->get('/')->assertOk()->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('diagnostics.failedTests', 2)
            ->where('diagnostics.averageDurationMilliseconds', 300)
            ->has('diagnostics.lastSuccessfulEchoAt')
            ->has('diagnostics.recentTests', 3)
            ->where('summary.unverifiedDicomNodes', 1));
    }

    public function test_diagnostic_details_are_hidden_without_existing_view_permission(): void
    {
        $this->withoutVite();
        DiagnosticTestRun::factory()->create();

        $this->actingAs(User::factory()->create())->get('/')->assertInertia(fn ($page) => $page->where('diagnostics', null));
    }
}
