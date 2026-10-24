<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DiagnosticTestRun;
use App\Models\DicomNode;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Site;
use App\Models\System;
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

    public function test_echo_status_groups_do_not_count_unsupported_or_incomplete_results_as_success(): void
    {
        $this->withoutVite();
        $system = System::factory()->create(['name' => 'Prüfstatus-Testsystem']);
        DicomNode::factory()->for($system)->create(['last_verified_at' => now(), 'last_verification_status' => 'success']);
        DicomNode::factory()->for($system)->create(['last_verified_at' => now(), 'last_verification_status' => 'failed']);
        DicomNode::factory()->for($system)->create();
        DicomNode::factory()->for($system)->create(['last_verification_status' => 'success']);
        DicomNode::factory()->for($system)->create(['last_verified_at' => now()]);
        DicomNode::factory()->for($system)->create(['supports_echo' => false]);
        DicomNode::factory()->for($system)->create(['supports_echo' => false, 'last_verified_at' => now(), 'last_verification_status' => 'success']);
        DicomNode::factory()->for($system)->create(['archived_at' => now(), 'last_verified_at' => now(), 'last_verification_status' => 'success']);

        $this->actingAs(User::factory()->create())->get('/')->assertInertia(fn ($page) => $page
            ->where('summary.dicomNodes', 7)
            ->where('summary.successfulDicomNodes', 1)
            ->where('summary.failedDicomNodes', 1)
            ->where('summary.unverifiedDicomNodes', 3)
            ->where('summary.notTestableDicomNodes', 2));
    }

    public function test_unsupported_node_does_not_complete_the_echo_task(): void
    {
        $this->withoutVite();
        DicomNode::factory()->create(['supports_echo' => false]);
        $this->actingAs(User::factory()->create())->get('/')->assertInertia(fn ($page) => $page->where('tasks.3.completed', false));
    }

    public function test_diagnostic_details_are_hidden_without_existing_view_permission(): void
    {
        $this->withoutVite();
        DiagnosticTestRun::factory()->create();

        $this->actingAs(User::factory()->create())->get('/')->assertInertia(fn ($page) => $page->where('diagnostics', null));
    }
}
