<?php

namespace Tests\Feature;

use App\Models\DicomDiscoveryResult;
use App\Models\DiscoveredHost;
use App\Models\DiscoveredPort;
use App\Models\DiscoveryRun;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DiscoveryResultExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_viewer_can_export_filtered_discovery_results_as_csv(): void
    {
        $run = DiscoveryRun::factory()->create(['name' => 'Netzwerkscan']);
        $included = DiscoveredHost::factory()->create([
            'discovery_run_id' => $run->id,
            'ip_address' => '192.168.20.10',
            'hostname' => '=unsafe-host',
            'status' => DiscoveredHost::STATUS_CONFIRMED,
            'confidence_score' => DiscoveredHost::CONFIDENCE_HIGH,
            'confidence_percentage' => 84,
            'suggested_system_type' => 'pacs',
        ]);
        DiscoveredPort::factory()->create(['discovered_host_id' => $included->id, 'port' => 11112]);
        DicomDiscoveryResult::factory()->create([
            'discovered_host_id' => $included->id,
            'port' => 11112,
            'called_ae' => 'PACS_ARCHIVE',
        ]);
        DiscoveredHost::factory()->create([
            'discovery_run_id' => $run->id,
            'ip_address' => '192.168.20.11',
            'status' => DiscoveredHost::STATUS_IGNORED,
        ]);

        $response = $this->actingAs($this->viewer())
            ->get("/discovery/runs/{$run->public_id}/export/csv?status=confirmed&successful_echo_only=1");

        $response->assertOk()->assertDownload()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        self::assertStringContainsString('192.168.20.10', $content);
        self::assertStringContainsString("'=unsafe-host", $content);
        self::assertStringContainsString('PACS_ARCHIVE@192.168.20.10:11112', $content);
        self::assertStringNotContainsString('192.168.20.11', $content);
        $this->assertDatabaseHas('security_events', [
            'event_type' => 'discovery.results-exported',
            'subject_public_id' => $run->public_id,
        ]);
    }

    public function test_viewer_can_export_filtered_discovery_results_as_json(): void
    {
        $run = DiscoveryRun::factory()->create(['name' => 'JSON Lauf']);
        $host = DiscoveredHost::factory()->create([
            'discovery_run_id' => $run->id,
            'ip_address' => '10.20.30.40',
            'confidence_score' => DiscoveredHost::CONFIDENCE_VERY_HIGH,
            'confidence_percentage' => 97,
        ]);
        DiscoveredPort::factory()->create(['discovered_host_id' => $host->id, 'port' => 104]);

        $response = $this->actingAs($this->viewer())
            ->get("/discovery/runs/{$run->public_id}/export/json?confidence=very_high");

        $response->assertOk()->assertDownload()->assertHeader('content-type', 'application/json; charset=UTF-8');
        $payload = json_decode($response->streamedContent(), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame($run->public_id, $payload['run']['public_id']);
        self::assertSame(['confidence' => 'very_high'], $payload['filters']);
        self::assertSame('10.20.30.40', $payload['results'][0]['ip_address']);
        self::assertSame([104], $payload['results'][0]['open_ports']);
    }

    public function test_viewer_can_export_filtered_review_queue_as_pdf(): void
    {
        $run = DiscoveryRun::factory()->create(['name' => 'Radiologie Scan', 'ip_range' => '10.40.0.0/24']);
        $included = DiscoveredHost::factory()->create([
            'discovery_run_id' => $run->id,
            'ip_address' => '10.40.0.20',
            'hostname' => 'pacs-prod',
            'status' => DiscoveredHost::STATUS_CONFIRMED,
            'confidence_score' => DiscoveredHost::CONFIDENCE_HIGH,
            'confidence_percentage' => 88,
            'suggested_system_type' => 'pacs',
        ]);
        DiscoveredPort::factory()->create([
            'discovered_host_id' => $included->id,
            'port' => 11112,
            'is_open' => true,
            'is_dicom_candidate' => true,
        ]);
        DicomDiscoveryResult::factory()->create([
            'discovered_host_id' => $included->id,
            'port' => 11112,
            'called_ae' => 'PACS_PROD',
            'echo_successful' => true,
        ]);
        DiscoveredHost::factory()->create([
            'discovery_run_id' => $run->id,
            'ip_address' => '10.40.0.21',
            'status' => DiscoveredHost::STATUS_IGNORED,
        ]);

        $response = $this->actingAs($this->viewer())
            ->get("/discovery/runs/{$run->public_id}/export/pdf?status=confirmed");

        $response->assertOk()->assertDownload()->assertHeader('content-type', 'application/pdf');
        $content = $response->getContent();
        self::assertStringStartsWith('%PDF-1.4', $content);
        self::assertStringContainsString('Discovery-Ergebnisbericht', $content);
        self::assertStringContainsString('10.40.0.20', $content);
        self::assertStringContainsString('PACS_PROD@10.40.0.20:11112', $content);
        self::assertStringNotContainsString('10.40.0.21', $content);
        $this->assertDatabaseHas('security_events', [
            'event_type' => 'discovery.results-exported',
            'subject_public_id' => $run->public_id,
        ]);
        $event = SecurityEvent::query()->where('event_type', 'discovery.results-exported')->latest('id')->firstOrFail();
        self::assertSame('pdf', $event->metadata['format']);
        self::assertSame(['status' => 'confirmed'], $event->metadata['filters']);
    }

    public function test_export_requires_view_permission_and_a_supported_format(): void
    {
        $this->seed();
        $run = DiscoveryRun::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->get("/discovery/runs/{$run->public_id}/export/csv")->assertForbidden();
        $this->actingAs($this->viewer())->get("/discovery/runs/{$run->public_id}/export/xml")->assertNotFound();
    }

    private function viewer(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $role = Role::query()->create(['name' => 'discovery-viewer-'.uniqid(), 'display_name' => 'Discovery Viewer']);
        $role->permissions()->attach(Permission::query()->where('name', 'discovery.view')->firstOrFail());
        $user->roles()->attach($role);

        return $user;
    }
}
