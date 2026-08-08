<?php

namespace Tests\Feature;

use App\Models\RegistryDocumentVersion;
use App\Models\SecurityEvent;
use App\Models\User;
use App\Support\RbacBootstrapper;
use App\Support\RegistryDocumentApprovalStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RegistryDocumentApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_uploader_cannot_approve_own_version_even_as_system_administrator(): void
    {
        $uploader = $this->administrator();
        $version = $this->pendingVersion($uploader);

        $this->actingAs($uploader)->post("/registry-document-versions/{$version->public_id}/approve")->assertForbidden();

        self::assertSame(RegistryDocumentApprovalStatus::PendingReview, $version->refresh()->approval_status);
    }

    public function test_second_person_can_approve_clean_pending_version_and_audit_event_is_chained(): void
    {
        $uploader = $this->administrator();
        $reviewer = $this->administrator();
        $version = $this->pendingVersion($uploader);

        $this->actingAs($reviewer)->post("/registry-document-versions/{$version->public_id}/approve", [
            'review_note' => 'Vier Augen bestätigt',
        ])->assertSessionHasNoErrors();

        $version->refresh();
        self::assertSame(RegistryDocumentApprovalStatus::Approved, $version->approval_status);
        self::assertSame($reviewer->id, $version->reviewed_by);
        self::assertSame($version->id, $version->document->current_version_id);
        $event = SecurityEvent::query()->where('event_type', 'document.approved')->firstOrFail();
        self::assertNotNull($event->event_hash);
        self::assertSame($version->public_id, $event->metadata['version_public_id']);
    }

    public function test_draft_can_be_submitted_and_rejection_requires_a_second_person_and_reason(): void
    {
        $uploader = $this->administrator();
        $reviewer = $this->administrator();
        $version = RegistryDocumentVersion::factory()->create([
            'uploaded_by' => $uploader->id,
            'approval_status' => 'draft',
            'malware_scan_status' => 'clean',
        ]);

        $this->actingAs($uploader)->post("/registry-document-versions/{$version->public_id}/submit-review")
            ->assertSessionHasNoErrors();
        self::assertSame(RegistryDocumentApprovalStatus::PendingReview, $version->refresh()->approval_status);
        $this->actingAs($reviewer)->post("/registry-document-versions/{$version->public_id}/reject")
            ->assertSessionHasErrors('review_note');
        $this->actingAs($reviewer)->post("/registry-document-versions/{$version->public_id}/reject", [
            'review_note' => 'Metadaten unvollständig',
        ])->assertSessionHasNoErrors();
        self::assertSame(RegistryDocumentApprovalStatus::Rejected, $version->refresh()->approval_status);
        $this->assertDatabaseHas('security_events', ['event_type' => 'document.review_requested']);
        $this->assertDatabaseHas('security_events', ['event_type' => 'document.rejected']);
    }

    public function test_unapproved_clean_version_cannot_be_downloaded(): void
    {
        $version = RegistryDocumentVersion::factory()->create([
            'approval_status' => 'pending_review',
            'malware_scan_status' => 'clean',
        ]);

        $this->actingAs($this->administrator())
            ->get("/registry-document-versions/{$version->public_id}/download")
            ->assertStatus(423);
    }

    private function pendingVersion(User $uploader): RegistryDocumentVersion
    {
        return RegistryDocumentVersion::factory()->create([
            'uploaded_by' => $uploader->id,
            'approval_status' => 'pending_review',
            'malware_scan_status' => 'clean',
        ]);
    }

    private function administrator(): User
    {
        $role = app(RbacBootstrapper::class)->ensureSystemAdministratorRole();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
