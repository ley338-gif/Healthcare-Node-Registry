<?php

namespace Tests\Feature;

use App\Models\DicomNode;
use App\Models\DiscoveredHost;
use App\Models\Organization;
use App\Models\System;
use App\Services\Discovery\RegistryPromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RegistryPromotionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_detects_a_hostname_duplicate(): void
    {
        $existing = System::factory()->create(['hostname' => 'pacs-archive-01', 'ip_address' => '10.0.0.5']);
        $host = DiscoveredHost::factory()->create(['ip_address' => '192.168.20.10', 'hostname' => 'pacs-archive-01']);

        $matches = (new RegistryPromotionService)->findDuplicates($host, 'PACS-ARCHIVE-01', 'PACS01', 11112);

        self::assertTrue(collect($matches)->contains(fn (array $m) => $m['type'] === 'hostname' && $m['system']->is($existing)));
    }

    public function test_it_detects_an_ae_title_duplicate(): void
    {
        $system = System::factory()->create();
        $node = DicomNode::factory()->create(['system_id' => $system->id, 'ae_title' => 'PACS01']);
        $host = DiscoveredHost::factory()->create(['ip_address' => '192.168.20.20']);

        $matches = (new RegistryPromotionService)->findDuplicates($host, 'Beliebiger Name', 'pacs01', 104);

        self::assertTrue(collect($matches)->contains(fn (array $m) => $m['type'] === 'ae_title' && $m['dicom_node']->is($node)));
    }

    public function test_it_detects_an_ip_and_port_duplicate(): void
    {
        $system = System::factory()->create();
        $node = DicomNode::factory()->create(['system_id' => $system->id, 'host' => '192.168.20.30', 'port' => 104]);
        $host = DiscoveredHost::factory()->create(['ip_address' => '192.168.20.30', 'hostname' => null]);

        $matches = (new RegistryPromotionService)->findDuplicates($host, 'Anderer Name', 'ANDERE_AE', 104);

        self::assertTrue(collect($matches)->contains(fn (array $m) => $m['type'] === 'ip_and_port' && $m['dicom_node']->is($node)));
    }

    public function test_it_detects_a_similar_system_name(): void
    {
        $existing = System::factory()->create(['name' => 'PACS-ARCHIVE-01', 'ip_address' => '10.0.0.9']);
        $host = DiscoveredHost::factory()->create(['ip_address' => '192.168.20.40']);

        $matches = (new RegistryPromotionService)->findDuplicates($host, 'PACS-ARCHIVE-1', 'X', 104);

        self::assertTrue(collect($matches)->contains(fn (array $m) => $m['type'] === 'similar_name' && $m['system']->is($existing)));
    }

    public function test_no_duplicates_are_reported_for_an_unrelated_host(): void
    {
        System::factory()->create(['ip_address' => '10.0.0.1', 'hostname' => 'unrelated']);
        $host = DiscoveredHost::factory()->create(['ip_address' => '192.168.20.50', 'hostname' => 'brand-new-host']);

        $matches = (new RegistryPromotionService)->findDuplicates($host, 'Brand New System', 'NEWAE', 104);

        self::assertSame([], $matches);
    }

    public function test_promote_creates_a_system_with_a_provenance_note_and_confirms_the_host(): void
    {
        $host = DiscoveredHost::factory()->create(['ip_address' => '192.168.20.60', 'confidence_percentage' => 77]);

        $system = (new RegistryPromotionService)->promote(
            host: $host,
            systemData: ['organization_id' => Organization::query()->create(['name' => 'Musterklinikum Nord'])->id, 'name' => 'CT-SOMATOM-01', 'system_type' => 'ct', 'status' => 'active', 'ip_address' => $host->ip_address],
            dicomNodeData: ['ae_title' => 'CT_RAD_01', 'host' => $host->ip_address, 'port' => 104, 'role' => 'scp'],
            existingSystem: null,
            discoveryRunId: $host->discovery_run_id,
            originalConfidencePercentage: $host->confidence_percentage,
        );

        self::assertStringContainsString('Discovery', (string) $system->notes);
        self::assertStringContainsString('77%', (string) $system->notes);
        $host->refresh();
        self::assertSame(DiscoveredHost::STATUS_CONFIRMED, $host->status);
        self::assertSame($system->id, $host->system_id);
    }
}
