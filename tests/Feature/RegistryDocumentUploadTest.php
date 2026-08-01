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
