<?php

namespace Tests\Unit;

use App\Models\DiscoveredHost;
use App\Services\Discovery\Classification\ClassificationContext;
use App\Services\Discovery\Classification\ClassificationService;
use PHPUnit\Framework\TestCase;

final class ClassificationServiceTest extends TestCase
{
    public function test_a_host_with_no_evidence_is_classified_as_unknown(): void
    {
        $service = new ClassificationService;
        $host = new DiscoveredHost(['hostname' => null]);

        $result = $service->classify($host, new ClassificationContext(
            hostname: null,
            openDicomCandidatePorts: [],
            successfulEchoes: [],
            existingRegistryMatch: false,
            webPortOpen: false,
            databasePortOpen: false,
        ));

        self::assertSame(0, $result->percentage);
        self::assertSame(DiscoveredHost::CONFIDENCE_UNKNOWN, $result->confidenceLevel);
        self::assertSame('unbekannt', $result->suggestedSystemType);
        self::assertSame([], $result->evidence);
    }

    public function test_a_successful_echo_with_hostname_keyword_yields_very_high_confidence(): void
    {
        $service = new ClassificationService;
        $host = new DiscoveredHost(['hostname' => 'pacs-archive-01']);

        $result = $service->classify($host, new ClassificationContext(
            hostname: null,
            openDicomCandidatePorts: [11112],
            successfulEchoes: [['port' => 11112, 'called_ae' => 'PACS01']],
            existingRegistryMatch: false,
            webPortOpen: false,
            databasePortOpen: false,
        ));

        self::assertSame('pacs', $result->suggestedSystemType);
        self::assertGreaterThanOrEqual(80, $result->percentage);
        self::assertSame(DiscoveredHost::CONFIDENCE_VERY_HIGH, $result->confidenceLevel);
        self::assertContains('dicom_echo_successful', array_map(fn ($e) => $e->ruleName, $result->evidence));
        self::assertContains('hostname_keyword_match', array_map(fn ($e) => $e->ruleName, $result->evidence));
    }

    public function test_confidence_percentage_is_capped_at_100(): void
    {
        $service = new ClassificationService;
        $host = new DiscoveredHost(['hostname' => 'pacs-archive-mwl-ct']);

        $result = $service->classify($host, new ClassificationContext(
            hostname: null,
            openDicomCandidatePorts: [104, 11112],
            successfulEchoes: [
                ['port' => 104, 'called_ae' => 'PACS01'],
                ['port' => 11112, 'called_ae' => 'MWL01'],
            ],
            existingRegistryMatch: true,
            webPortOpen: true,
            databasePortOpen: true,
        ));

        self::assertSame(100, $result->percentage);
        self::assertSame(DiscoveredHost::CONFIDENCE_VERY_HIGH, $result->confidenceLevel);
    }

    public function test_an_open_candidate_port_alone_does_not_imply_high_confidence(): void
    {
        $service = new ClassificationService;
        $host = new DiscoveredHost(['hostname' => null]);

        $result = $service->classify($host, new ClassificationContext(
            hostname: null,
            openDicomCandidatePorts: [104],
            successfulEchoes: [],
            existingRegistryMatch: false,
            webPortOpen: false,
            databasePortOpen: false,
        ));

        self::assertSame(15, $result->percentage);
        self::assertSame(DiscoveredHost::CONFIDENCE_LOW, $result->confidenceLevel);
    }
}
