<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Organization;
use App\Models\RegistryDocument;
use App\Models\RegistryDocumentVersion;
use App\Models\Site;
use App\Models\System;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RegistryDocumentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_documents_are_polymorphically_assigned_to_supported_registry_entities(): void
    {
        [$organization, $site, $department, $system] = $this->structure();

        foreach ([$organization, $site, $department, $system] as $context) {
            $document = $context->documents()->create($this->documentAttributes());

            self::assertNotNull($document->public_id);
            self::assertSame('public_id', $document->getRouteKeyName());
            self::assertTrue($document->documentable->is($context));
            self::assertTrue($context->documents()->firstOrFail()->is($document));
        }

        self::assertSame(4, RegistryDocument::query()->count());
    }

    public function test_document_keeps_versions_and_marks_one_current_version(): void
    {
        $document = RegistryDocument::factory()->create();
        $first = RegistryDocumentVersion::factory()->for($document, 'document')->create([
            'version_number' => 1,
            'change_note' => 'Erstfassung',
        ]);
        $second = RegistryDocumentVersion::factory()->for($document, 'document')->create([
            'version_number' => 2,
            'change_note' => 'Aktualisierte Fassung',
        ]);
        $document->update(['current_version_id' => $second->id]);

        self::assertCount(2, $document->versions);
        self::assertTrue($document->currentVersion->is($second));
        self::assertTrue($first->document->is($document));
        self::assertNotNull($first->public_id);
        self::assertSame('public_id', $first->getRouteKeyName());
    }

    public function test_version_numbers_are_unique_within_a_document(): void
    {
        $document = RegistryDocument::factory()->create();
        RegistryDocumentVersion::factory()->for($document, 'document')->create(['version_number' => 1]);

        $this->expectException(QueryException::class);
        RegistryDocumentVersion::factory()->for($document, 'document')->create(['version_number' => 1]);
    }

    public function test_current_version_must_belong_to_the_same_document(): void
    {
        $document = RegistryDocument::factory()->create();
        $foreignVersion = RegistryDocumentVersion::factory()->create();

        $this->expectException(QueryException::class);
        $document->update(['current_version_id' => $foreignVersion->id]);
    }

    public function test_document_metadata_uses_expected_immutable_casts(): void
    {
        $document = RegistryDocument::factory()->create([
            'tags' => ['vertrag', 'pacs'],
            'valid_from' => '2026-08-01',
            'valid_until' => '2027-07-31',
            'archived_at' => '2026-08-01 12:00:00+00',
        ]);
        $version = RegistryDocumentVersion::factory()->for($document, 'document')->create([
            'metadata' => ['source' => 'registry'],
        ]);

        self::assertSame(['vertrag', 'pacs'], $document->tags);
        self::assertInstanceOf(CarbonImmutable::class, $document->valid_from);
        self::assertInstanceOf(CarbonImmutable::class, $document->valid_until);
        self::assertInstanceOf(CarbonImmutable::class, $document->archived_at);
        self::assertInstanceOf(CarbonImmutable::class, $version->uploaded_at);
        self::assertSame(['source' => 'registry'], $version->metadata);
        self::assertSame('pending', $version->malware_scan_status);
    }

    /** @return array{Organization, Site, Department, System} */
    private function structure(): array
    {
        $organization = Organization::query()->create(['name' => 'Dokumentenverbund']);
        $site = Site::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Dokumentenstandort',
            'country_code' => 'DE',
            'timezone' => 'Europe/Berlin',
        ]);
        $department = Department::query()->create(['site_id' => $site->id, 'name' => 'Dokumentenabteilung']);
        $system = System::factory()->create([
            'organization_id' => $organization->id,
            'site_id' => $site->id,
            'department_id' => $department->id,
        ]);

        return [$organization, $site, $department, $system];
    }

    /** @return array<string, mixed> */
    private function documentAttributes(): array
    {
        $creator = User::factory()->create();

        return [
            'title' => 'Betriebshandbuch',
            'description' => 'Freigegebene Betriebsunterlagen',
            'category' => 'other',
            'visibility' => 'internal',
            'status' => 'active',
            'tags' => [],
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
        ];
    }
}
