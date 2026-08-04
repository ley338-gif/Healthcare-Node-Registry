<?php

namespace Tests\Feature;

use App\Models\DicomConnection;
use App\Models\DicomNode;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FirewallMatrixReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        fake()->unique(true);
    }

    public function test_report_contains_effective_target_port_and_tls_state(): void
    {
        $connection = $this->connection(['port_override' => 2762, 'tls_enabled' => true]);
        $this->actingAs($this->administrator())->get('/reports/firewall-matrix')
            ->assertInertia(fn ($page) => $page->component('Reports/FirewallMatrix')->has('rows', 1)->where('rows.0.public_id', $connection->public_id)->where('rows.0.port', 2762)->where('rows.0.tls_enabled', true));
    }

    public function test_organization_filter_matches_source_or_target_context(): void
    {
        $connection = $this->connection();
        $organization = $connection->targetNode->system->organization;
        DicomConnection::factory()->create();

        $this->actingAs($this->administrator())->get('/reports/firewall-matrix?organization='.$organization->public_id)
            ->assertInertia(fn ($page) => $page->has('rows', 1)->where('rows.0.public_id', $connection->public_id));
    }

    public function test_csv_and_pdf_exports_contain_filtered_report(): void
    {
        $connection = $this->connection(['service' => 'store']);
        $system = $connection->sourceNode->system;
        $query = '?system='.$system->public_id;

        $csv = $this->actingAs($this->administrator())->get('/reports/firewall-matrix/export/csv'.$query);
        $csv->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        self::assertStringContainsString($connection->targetNode->host, $csv->streamedContent());

        $pdf = $this->actingAs($this->administrator())->get('/reports/firewall-matrix/export/pdf'.$query);
        $pdf->assertOk()->assertHeader('content-type', 'application/pdf');
        self::assertStringStartsWith('%PDF-1.4', $pdf->getContent());
        self::assertStringContainsString('Firewall- und Portmatrix', $pdf->getContent());
    }

    /** @param array<string,mixed> $attributes */
    private function connection(array $attributes = []): DicomConnection
    {
        $sourceSystem = System::factory()->create(['name' => 'CT Produktion']);
        $targetSystem = System::factory()->create(['name' => 'PACS Produktion']);
        $source = DicomNode::factory()->create(['system_id' => $sourceSystem->id, 'host' => '10.20.30.10', 'ae_title' => 'CT_PROD']);
        $target = DicomNode::factory()->create(['system_id' => $targetSystem->id, 'host' => '10.20.40.10', 'port' => 104, 'ae_title' => 'PACS_PROD']);

        return DicomConnection::factory()->create(['source_dicom_node_id' => $source->id, 'target_dicom_node_id' => $target->id, 'calling_ae_title' => 'CT_PROD', 'called_ae_title' => 'PACS_PROD', ...$attributes]);
    }

    private function administrator(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());

        return $user;
    }
}
