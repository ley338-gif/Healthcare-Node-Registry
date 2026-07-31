<?php

namespace Tests\Feature;

use App\Models\DiagnosticTestRun;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DiagnosticResultExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_export_sanitized_json(): void
    {
        $run = DiagnosticTestRun::factory()->create(['details' => [
            'password' => 'secret', 'patientId' => 'REAL-PATIENT', 'log' => 'Failure at /var/www/html/internal/Runner.php',
        ]]);
        $response = $this->actingAs($this->administrator())->get("/tests/history/{$run->public_id}/export/json");

        $response->assertOk()->assertDownload("diagnostic-{$run->public_id}.json");
        $content = $response->streamedContent();
        self::assertStringContainsString('[REDACTED]', $content);
        self::assertStringNotContainsString('secret', $content);
        self::assertStringNotContainsString('REAL-PATIENT', $content);
        self::assertStringNotContainsString('Runner.php', $content);
        $this->assertDatabaseHas('security_events', ['event_type' => 'diagnostics.result-exported', 'subject_public_id' => $run->public_id]);
    }

    public function test_tabular_results_are_exported_as_csv(): void
    {
        $run = DiagnosticTestRun::factory()->create(['test_type' => 'pacs_query', 'details' => ['results' => [
            ['studyInstanceUid' => '1.2.3', 'status' => 'accepted'],
            ['studyInstanceUid' => '1.2.4', 'status' => 'rejected'],
        ]]]);
        $response = $this->actingAs($this->administrator())->get("/tests/history/{$run->public_id}/export/csv");

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        self::assertStringContainsString('status,studyInstanceUid', $response->streamedContent());
        self::assertStringContainsString('rejected,1.2.4', $response->streamedContent());
    }

    public function test_export_permission_and_known_format_are_required(): void
    {
        $this->seed();
        $run = DiagnosticTestRun::factory()->create();
        $user = User::factory()->create();
        $role = Role::query()->create(['name' => 'viewer', 'display_name' => 'Viewer']);
        $role->permissions()->attach(Permission::query()->where('name', 'registry.view')->firstOrFail());
        $user->roles()->attach($role);

        $this->actingAs($user)->get("/tests/history/{$run->public_id}/export/json")->assertForbidden();
        $this->actingAs($this->administrator())->get("/tests/history/{$run->public_id}/export/xml")->assertNotFound();
    }

    private function administrator(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());

        return $user;
    }
}
