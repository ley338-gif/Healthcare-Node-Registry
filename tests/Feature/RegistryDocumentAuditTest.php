<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Organization;
use App\Models\RegistryDocument;
use App\Models\SecurityEvent;
use App\Models\Site;
use App\Models\System;
use App\Models\User;
use App\Services\Documents\MalwareScanner;
use App\Services\Documents\MalwareScanResult;
use App\Support\RbacBootstrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class RegistryDocumentAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_metadata_archive_and_restore_use_shared_registry_history(): void
    {
        $this->withoutVite();
        $system = System::factory()->create();
        $document = RegistryDocument::factory()->for($system, 'documentable')->create(['title' => 'Alter Vertrag']);
        $user = $this->administrator();

        $this->actingAs($user)->put("/registry-documents/{$document->public_id}", [
            'title' => 'Neuer Vertrag',
            'description' => 'Vertrauliche interne Vertragsdetails',
            'category' => 'support_contract',
            'visibility' => 'restricted',
            'valid_from' => '2026-01-01',
            'valid_until' => '2027-01-01',
            'contract_reference' => 'SUP-42',
            'tags' => ['support'],
        ])->assertSessionHasNoErrors();
        $this->actingAs($user)->post("/registry-documents/{$document->public_id}/archive")->assertSessionHasNoErrors();
        $this->actingAs($user)->post("/registry-documents/{$document->public_id}/restore")->assertSessionHasNoErrors();

        self::assertEqualsCanonicalizing(
            ['document.metadata_updated', 'document.archived', 'document.restored'],
            SecurityEvent::query()->pluck('event_type')->all(),
        );
        $metadata = SecurityEvent::query()->where('event_type', 'document.metadata_updated')->firstOrFail()->metadata;
        self::assertSame($document->public_id, $metadata['document_public_id']);
        self::assertSame(36, $metadata['after']['description']['length']);
        self::assertStringNotContainsString('Vertrauliche', json_encode($metadata, JSON_THROW_ON_ERROR));
        $this->actingAs($user)->get("/systems/{$system->public_id}?history_type=document.restored")
            ->assertInertia(fn ($page) => $page
                ->has('history.data', 1)
                ->where('history.data.0.event_type', 'document.restored')
                ->where('history.data.0.metadata.document_title', 'Neuer Vertrag'));
    }

    public function test_document_management_actions_require_existing_permissions(): void
    {
        $document = RegistryDocument::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->put("/registry-documents/{$document->public_id}", [])->assertForbidden();
        $this->actingAs($user)->post("/registry-documents/{$document->public_id}/archive")->assertForbidden();
        $this->actingAs($user)->post("/registry-documents/{$document->public_id}/restore")->assertForbidden();
        self::assertSame(0, SecurityEvent::query()->count());
    }

    public function test_infected_upload_creates_scan_event_without_raw_scanner_message(): void
    {
        Storage::fake('registry_documents');
        $user = $this->administrator();
        foreach (['infected' => 'failed', 'failed' => 'warning'] as $scanStatus => $auditStatus) {
            $this->app->instance(MalwareScanner::class, new class($scanStatus) implements MalwareScanner
            {
                public function __construct(private readonly string $status) {}

                public function scan(string $path): MalwareScanResult
                {
                    return new MalwareScanResult($this->status, 'Secret scanner infrastructure detail');
                }
            });
            $system = System::factory()->create();
            $this->actingAs($user)->post("/registry-documents/systems/{$system->public_id}", [
                'title' => "Scanstatus {$scanStatus}",
                'category' => 'other',
                'visibility' => 'internal',
                'file' => UploadedFile::fake()->createWithContent("{$scanStatus}.pdf", "%PDF-1.7 {$scanStatus}"),
            ])->assertSessionHasNoErrors();

            $event = SecurityEvent::query()->where('event_type', "document.scan_{$scanStatus}")->firstOrFail();
            self::assertSame($auditStatus, $event->metadata['status']);
            self::assertSame($scanStatus, $event->metadata['scan_status']);
            self::assertStringNotContainsString('Secret scanner', json_encode($event->metadata, JSON_THROW_ON_ERROR));
        }
    }

    public function test_upload_and_version_events_are_attributed_to_the_correct_registry_context(): void
    {
        $this->withoutVite();
        Storage::fake('registry_documents');
        $organization = Organization::query()->create(['name' => 'Auditverbund']);
        $site = Site::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Auditstandort',
            'country_code' => 'DE',
            'timezone' => 'Europe/Berlin',
        ]);
        $department = Department::query()->create(['site_id' => $site->id, 'name' => 'Auditabteilung']);
        $system = System::factory()->create([
            'organization_id' => $organization->id,
            'site_id' => $site->id,
            'department_id' => $department->id,
        ]);
        $contexts = [
            'organizations' => $organization,
            'sites' => $site,
            'departments' => $department,
            'systems' => $system,
        ];
        $user = $this->administrator();

        foreach ($contexts as $type => $context) {
            $this->actingAs($user)->post("/registry-documents/{$type}/{$context->public_id}", [
                'title' => "Audit {$type}",
                'category' => 'other',
                'visibility' => 'internal',
                'file' => UploadedFile::fake()->createWithContent("{$type}.pdf", "%PDF-1.7 {$type}"),
            ])->assertSessionHasNoErrors();

            $this->assertDatabaseHas('security_events', [
                'event_type' => 'document.created',
                'subject_type' => $context::class,
                'subject_public_id' => $context->public_id,
            ]);
        }

        $systemDocument = RegistryDocument::query()
            ->where('documentable_type', System::class)
            ->where('documentable_id', $system->id)
            ->firstOrFail();
        $this->actingAs($user)->post("/registry-documents/{$systemDocument->public_id}/versions", [
            'file' => UploadedFile::fake()->createWithContent('systems-v2.pdf', '%PDF-1.7 systems v2'),
            'change_note' => 'Auditversion',
        ])->assertSessionHasNoErrors();

        $this->actingAs($user)->get("/systems/{$system->public_id}?history_type=document.created")
            ->assertInertia(fn ($page) => $page
                ->has('history.data', 1)
                ->where('history.data.0.subject_public_id', $system->public_id));
        $this->actingAs($user)->get("/systems/{$system->public_id}?history_type=document.version_uploaded")
            ->assertInertia(fn ($page) => $page
                ->has('history.data', 1)
                ->where('history.data.0.subject_public_id', $system->public_id));
    }

    private function administrator(): User
    {
        $role = app(RbacBootstrapper::class)->ensureSystemAdministratorRole();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
