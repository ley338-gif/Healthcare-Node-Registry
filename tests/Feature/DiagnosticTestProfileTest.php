<?php

namespace Tests\Feature;

use App\Models\DiagnosticTestProfile;
use App\Models\DicomNode;
use App\Models\Role;
use App\Models\User;
use App\Services\Diagnostics\NetworkConnectionTest;
use App\Services\Diagnostics\NetworkProbe;
use App\Services\Diagnostics\NetworkProbeResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DiagnosticTestProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_profile(): void
    {
        $user = $this->manager();
        $node = DicomNode::factory()->create();

        $this->actingAs($user)->post('/tests/profiles', $this->input($node))->assertSessionHas('success');

        $this->assertDatabaseHas('diagnostic_test_profiles', [
            'name' => 'PACS Produktion Netzwerk',
            'dicom_node_id' => $node->id,
            'test_type' => 'network',
            'created_by' => $user->id,
        ]);
    }

    public function test_manager_can_update_and_archive_profile(): void
    {
        $user = $this->manager();
        $node = DicomNode::factory()->create();
        $profile = DiagnosticTestProfile::factory()->create(['dicom_node_id' => $node->id]);
        $input = $this->input($node);
        $input['name'] = 'Aktualisiertes Profil';

        $this->actingAs($user)->put("/tests/profiles/{$profile->public_id}", $input)->assertSessionHas('success');
        $this->actingAs($user)->post("/tests/profiles/{$profile->public_id}/archive")->assertSessionHas('success');

        self::assertSame('Aktualisiertes Profil', $profile->fresh()?->name);
        self::assertFalse($profile->fresh()?->enabled ?? true);
        self::assertNotNull($profile->fresh()?->archived_at);
    }

    public function test_profile_executes_registered_node_test(): void
    {
        $user = $this->manager();
        $node = DicomNode::factory()->create();
        $profile = DiagnosticTestProfile::factory()->create(['dicom_node_id' => $node->id, 'test_type' => 'network']);
        $this->app->instance(NetworkConnectionTest::class, new NetworkConnectionTest(new ProfileNetworkProbe));

        $this->actingAs($user)->post("/tests/profiles/{$profile->public_id}/execute")
            ->assertSessionHas('success')
            ->assertSessionHas('diagnosticResult.testType', 'network');

        $this->assertDatabaseHas('diagnostic_test_runs', ['dicom_node_id' => $node->id, 'test_type' => 'network']);
        $this->assertDatabaseHas('security_events', ['event_type' => 'diagnostics.profile.executed', 'subject_public_id' => $profile->public_id]);
    }

    public function test_unprivileged_user_cannot_manage_or_execute_profiles(): void
    {
        $node = DicomNode::factory()->create();
        $profile = DiagnosticTestProfile::factory()->create(['dicom_node_id' => $node->id]);
        $user = User::factory()->create();

        $this->actingAs($user)->post('/tests/profiles', $this->input($node))->assertForbidden();
        $this->actingAs($user)->post("/tests/profiles/{$profile->public_id}/execute")->assertForbidden();
    }

    public function test_disabled_profile_cannot_execute(): void
    {
        $profile = DiagnosticTestProfile::factory()->create(['enabled' => false]);

        $this->actingAs($this->manager())->post("/tests/profiles/{$profile->public_id}/execute")
            ->assertSessionHas('error');
        $this->assertDatabaseCount('diagnostic_test_runs', 0);
    }

    /** @return array<string, mixed> */
    private function input(DicomNode $node): array
    {
        return [
            'name' => 'PACS Produktion Netzwerk', 'description' => 'Regelmäßiger Verbindungstest',
            'test_type' => 'network', 'dicom_node_public_id' => $node->public_id,
            'calling_ae_title' => 'NODE_REGISTRY', 'configuration' => [],
            'timeout_seconds' => 15, 'enabled' => true,
        ];
    }

    private function manager(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());

        return $user;
    }
}

final class ProfileNetworkProbe implements NetworkProbe
{
    public function resolve(string $host): array
    {
        return ['192.0.2.10'];
    }

    public function connect(string $host, int $port, float $timeoutSeconds): NetworkProbeResult
    {
        return new NetworkProbeResult(true);
    }
}
