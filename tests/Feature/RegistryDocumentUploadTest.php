<?php

namespace Tests\Feature;

use App\Models\RegistryDocument;
use App\Models\RegistryDocumentVersion;
use App\Models\System;
use App\Models\User;
use App\Support\RbacBootstrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class RegistryDocumentUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_upload_is_privately_stored_with_safe_metadata(): void
    {
        Storage::fake('registry_documents');
        $system = System::factory()->create();
        $response = $this->actingAs($this->administrator())->post("/registry-documents/systems/{$system->public_id}", [
            ...$this->metadata(),
            'file' => UploadedFile::fake()->createWithContent('../Wartung Vertrag.pdf', "%PDF-1.7\ncontents"),
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();
        $document = RegistryDocument::query()->firstOrFail();
        $version = RegistryDocumentVersion::query()->firstOrFail();
        self::assertSame($system->id, $document->documentable_id);
        self::assertSame($version->id, $document->current_version_id);
        self::assertSame('Wartung Vertrag.pdf', $version->original_filename);
        self::assertNotSame($version->original_filename, $version->stored_filename);
        self::assertSame(hash('sha256', "%PDF-1.7\ncontents"), $version->sha256);
        self::assertSame('unavailable', $version->malware_scan_status);
        Storage::disk('registry_documents')->assertExists($version->storage_path);
        $this->assertDatabaseHas('security_events', ['event_type' => 'document.created', 'subject_public_id' => $system->public_id]);
    }

    public function test_upload_requires_document_permission(): void
    {
        Storage::fake('registry_documents');
        $system = System::factory()->create();
        $this->actingAs(User::factory()->create())->post("/registry-documents/systems/{$system->public_id}", [
            ...$this->metadata(), 'file' => UploadedFile::fake()->createWithContent('file.pdf', '%PDF-1.7'),
        ])->assertForbidden();
        self::assertSame(0, RegistryDocument::query()->count());
    }

    public function test_mismatched_signature_and_blocked_extension_are_rejected(): void
    {
        Storage::fake('registry_documents');
        $system = System::factory()->create();
        $user = $this->administrator();
        $this->actingAs($user)->post("/registry-documents/systems/{$system->public_id}", [
            ...$this->metadata(), 'file' => UploadedFile::fake()->createWithContent('fake.pdf', 'not a pdf'),
        ])->assertSessionHasErrors('file');
        $this->actingAs($user)->post("/registry-documents/systems/{$system->public_id}", [
            ...$this->metadata(), 'file' => UploadedFile::fake()->createWithContent('active.svg', '<svg/>'),
        ])->assertSessionHasErrors('file');
    }

    public function test_upload_rejects_files_above_the_configured_size_limit(): void
    {
        Storage::fake('registry_documents');
        config()->set('registry_documents.max_upload_kb', 1);
        $system = System::factory()->create();

        $this->actingAs($this->administrator())->post("/registry-documents/systems/{$system->public_id}", [
            ...$this->metadata(),
            'file' => UploadedFile::fake()->createWithContent('large.pdf', "%PDF-1.7\n".str_repeat('x', 2048)),
        ])->assertSessionHasErrors('file');

        self::assertSame(0, RegistryDocument::query()->count());
        Storage::disk('registry_documents')->assertDirectoryEmpty('');
    }

    public function test_upload_rejects_wrong_and_archived_registry_contexts(): void
    {
        Storage::fake('registry_documents');
        $system = System::factory()->create();
        $archivedSystem = System::factory()->create(['archived_at' => now()]);
        $user = $this->administrator();
        $file = fn () => UploadedFile::fake()->createWithContent('file.pdf', '%PDF-1.7 context');

        $this->actingAs($user)->post("/registry-documents/organizations/{$system->public_id}", [
            ...$this->metadata(), 'file' => $file(),
        ])->assertNotFound();
        $this->actingAs($user)->post("/registry-documents/systems/{$archivedSystem->public_id}", [
            ...$this->metadata(), 'file' => $file(),
        ])->assertStatus(422);

        self::assertSame(0, RegistryDocument::query()->count());
    }

    public function test_duplicate_file_for_same_context_is_rejected(): void
    {
        Storage::fake('registry_documents');
        $system = System::factory()->create();
        $user = $this->administrator();
        foreach ([1, 2] as $attempt) {
            $response = $this->actingAs($user)->post("/registry-documents/systems/{$system->public_id}", [
                ...$this->metadata(), 'title' => "Dokument {$attempt}",
                'file' => UploadedFile::fake()->createWithContent('same.pdf', '%PDF-1.7 same'),
            ]);
            $attempt === 1 ? $response->assertSessionHasNoErrors() : $response->assertSessionHasErrors('file');
        }
        self::assertSame(1, RegistryDocumentVersion::query()->count());
    }

    public function test_new_version_becomes_current_and_keeps_previous_version(): void
    {
        Storage::fake('registry_documents');
        $system = System::factory()->create();
        $user = $this->administrator();
        $this->actingAs($user)->post("/registry-documents/systems/{$system->public_id}", [
            ...$this->metadata(), 'file' => UploadedFile::fake()->createWithContent('version-1.pdf', '%PDF-1.7 first'),
        ])->assertSessionHasNoErrors();
        $document = RegistryDocument::query()->firstOrFail();
        $firstVersionId = $document->current_version_id;

        $this->actingAs($user)->post("/registry-documents/{$document->public_id}/versions", [
            'file' => UploadedFile::fake()->createWithContent('version-2.pdf', '%PDF-1.7 second'),
            'change_note' => 'Vertrag verlängert',
        ])->assertSessionHasNoErrors();

        $document->refresh();
        self::assertSame(2, $document->versions()->count());
        self::assertNotSame($firstVersionId, $document->current_version_id);
        self::assertSame(2, $document->currentVersion?->version_number);
        self::assertSame('Vertrag verlängert', $document->currentVersion?->change_note);
        $this->assertDatabaseHas('security_events', ['event_type' => 'document.version_uploaded', 'subject_public_id' => $system->public_id]);
    }

    public function test_new_version_retains_old_file_and_document_metadata(): void
    {
        Storage::fake('registry_documents');
        $system = System::factory()->create();
        $user = $this->administrator();
        $metadata = [
            ...$this->metadata(),
            'description' => 'Technische Vertragsunterlagen',
            'valid_from' => '2026-01-01',
            'valid_until' => '2027-01-01',
            'contract_reference' => 'MAINT-42',
            'tags' => ['wartung', 'kritisch'],
        ];
        $this->actingAs($user)->post("/registry-documents/systems/{$system->public_id}", [
            ...$metadata, 'file' => UploadedFile::fake()->createWithContent('version-1.pdf', '%PDF-1.7 retained'),
        ])->assertSessionHasNoErrors();
        $document = RegistryDocument::query()->firstOrFail();
        $firstVersion = $document->currentVersion;
        self::assertNotNull($firstVersion);

        $this->actingAs($user)->post("/registry-documents/{$document->public_id}/versions", [
            'file' => UploadedFile::fake()->createWithContent('version-2.pdf', '%PDF-1.7 replacement'),
            'change_note' => 'Neue Fassung',
        ])->assertSessionHasNoErrors();

        $document->refresh();
        self::assertSame('Wartungsvertrag', $document->title);
        self::assertSame('Technische Vertragsunterlagen', $document->description);
        self::assertSame('MAINT-42', $document->contract_reference);
        self::assertSame(['wartung', 'kritisch'], $document->tags);
        self::assertNotSame($firstVersion->id, $document->current_version_id);
        self::assertTrue($document->versions()->whereKey($firstVersion->id)->exists());
        Storage::disk('registry_documents')->assertExists($firstVersion->storage_path);
    }

    public function test_new_version_requires_permission_and_rejects_duplicate_content(): void
    {
        Storage::fake('registry_documents');
        $document = RegistryDocument::factory()->create();
        $file = fn () => UploadedFile::fake()->createWithContent('same.pdf', '%PDF-1.7 duplicate');
        $this->actingAs(User::factory()->create())->post("/registry-documents/{$document->public_id}/versions", [
            'file' => $file(), 'change_note' => 'Nicht erlaubt',
        ])->assertForbidden();
        $user = $this->administrator();
        $this->actingAs($user)->post("/registry-documents/{$document->public_id}/versions", ['file' => $file(), 'change_note' => 'Version 1'])->assertSessionHasNoErrors();
        $this->actingAs($user)->post("/registry-documents/{$document->public_id}/versions", ['file' => $file(), 'change_note' => 'Duplikat'])->assertSessionHasErrors('file');
        self::assertSame(1, $document->versions()->count());
    }

    public function test_clean_version_can_be_downloaded_with_permission(): void
    {
        Storage::fake('registry_documents');
        $version = RegistryDocumentVersion::factory()->create([
            'storage_disk' => 'registry_documents',
            'storage_path' => 'systems/test/document.pdf',
            'original_filename' => 'Wartungsvertrag.pdf',
            'mime_type' => 'application/pdf',
            'malware_scan_status' => 'clean',
        ]);
        Storage::disk('registry_documents')->put($version->storage_path, '%PDF-1.7 download');

        $response = $this->actingAs($this->administrator())->get("/registry-document-versions/{$version->public_id}/download");

        $response->assertOk()->assertDownload('Wartungsvertrag.pdf');
        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_version_download_requires_permission_and_clean_scan(): void
    {
        Storage::fake('registry_documents');
        $version = RegistryDocumentVersion::factory()->create([
            'storage_disk' => 'registry_documents',
            'storage_path' => 'systems/test/document.pdf',
            'malware_scan_status' => 'unavailable',
        ]);
        Storage::disk('registry_documents')->put($version->storage_path, 'contents');

        $this->actingAs(User::factory()->create())->get("/registry-document-versions/{$version->public_id}/download")->assertForbidden();
        $this->actingAs($this->administrator())->get("/registry-document-versions/{$version->public_id}/download")->assertStatus(423);
    }

    public function test_download_rejects_archived_documents_and_infected_versions(): void
    {
        Storage::fake('registry_documents');
        $version = RegistryDocumentVersion::factory()->create([
            'storage_disk' => 'registry_documents',
            'storage_path' => 'systems/test/infected.pdf',
            'malware_scan_status' => 'infected',
        ]);
        Storage::disk('registry_documents')->put($version->storage_path, '%PDF-1.7 infected');
        $user = $this->administrator();

        $this->actingAs($user)->get("/registry-document-versions/{$version->public_id}/download")->assertStatus(423);
        $version->document->update(['status' => 'archived', 'archived_at' => now()]);
        $this->actingAs($user)->get("/registry-document-versions/{$version->public_id}/download")->assertNotFound();
    }

    public function test_clean_pdf_version_can_be_previewed_privately_with_range_support(): void
    {
        Storage::fake('registry_documents');
        $version = RegistryDocumentVersion::factory()->create([
            'storage_disk' => 'registry_documents',
            'storage_path' => 'systems/test/preview.pdf',
            'original_filename' => 'Technisches Handbuch.pdf',
            'mime_type' => 'application/pdf',
            'file_extension' => 'pdf',
            'malware_scan_status' => 'clean',
        ]);
        Storage::disk('registry_documents')->put($version->storage_path, '%PDF-1.7 preview contents');

        $response = $this->actingAs($this->administrator())
            ->withHeader('Range', 'bytes=0-7')
            ->get("/registry-document-versions/{$version->public_id}/preview");

        $response->assertStatus(206)
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Range', 'bytes 0-7/25')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        self::assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
        self::assertStringContainsString("frame-ancestors 'self'", (string) $response->headers->get('Content-Security-Policy'));
    }

    public function test_preview_rejects_unauthorized_non_pdf_and_unscanned_versions(): void
    {
        Storage::fake('registry_documents');
        $version = RegistryDocumentVersion::factory()->create([
            'storage_disk' => 'registry_documents',
            'storage_path' => 'systems/test/document.txt',
            'mime_type' => 'text/plain',
            'file_extension' => 'txt',
            'malware_scan_status' => 'clean',
        ]);
        Storage::disk('registry_documents')->put($version->storage_path, 'plain text');

        $this->actingAs(User::factory()->create())->get("/registry-document-versions/{$version->public_id}/preview")->assertForbidden();
        $this->actingAs($this->administrator())->get("/registry-document-versions/{$version->public_id}/preview")->assertStatus(415);
        $version->update(['mime_type' => 'application/pdf', 'file_extension' => 'pdf', 'malware_scan_status' => 'pending']);
        $this->actingAs($this->administrator())->get("/registry-document-versions/{$version->public_id}/preview")->assertStatus(423);
    }

    public function test_private_document_file_is_not_exposed_through_public_storage(): void
    {
        Storage::fake('registry_documents');
        $version = RegistryDocumentVersion::factory()->create([
            'storage_disk' => 'registry_documents',
            'storage_path' => 'systems/private/document.pdf',
            'malware_scan_status' => 'clean',
        ]);
        Storage::disk('registry_documents')->put($version->storage_path, '%PDF-1.7 private');

        $this->get('/storage/'.$version->storage_path)->assertNotFound();
    }

    private function administrator(): User
    {
        $role = app(RbacBootstrapper::class)->ensureSystemAdministratorRole();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    /** @return array<string, mixed> */
    private function metadata(): array
    {
        return ['title' => 'Wartungsvertrag', 'category' => 'maintenance_contract', 'visibility' => 'internal'];
    }
}
