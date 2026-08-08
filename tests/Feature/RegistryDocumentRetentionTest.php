<?php

namespace Tests\Feature;

use App\Models\RegistryDocument;
use App\Models\RegistryDocumentVersion;
use App\Support\RegistryDocumentCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

final class RegistryDocumentRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_categories_resolve_configurable_retention_tiers(): void
    {
        config()->set('registry_documents.retention_days', ['short' => 30, 'standard' => 90, 'long' => 365]);

        self::assertSame(365, RegistryDocumentCategory::SupportContract->retentionDays());
        self::assertSame(90, RegistryDocumentCategory::Sop->retentionDays());
        self::assertSame(30, RegistryDocumentCategory::Other->retentionDays());
    }

    public function test_retention_deadline_prevents_early_document_and_version_deletion(): void
    {
        $document = RegistryDocument::factory()->create(['retention_until' => now()->addDay()]);
        $version = RegistryDocumentVersion::factory()->for($document, 'document')->create();

        try {
            $version->delete();
            self::fail('Version deletion should have been blocked.');
        } catch (LogicException $exception) {
            self::assertStringContainsString('Aufbewahrungsfrist', $exception->getMessage());
        }

        $this->expectException(LogicException::class);
        $document->delete();
    }

    public function test_document_can_be_deleted_after_retention_deadline(): void
    {
        $document = RegistryDocument::factory()->create(['retention_until' => now()->subDay()]);

        self::assertTrue($document->delete());
    }
}
