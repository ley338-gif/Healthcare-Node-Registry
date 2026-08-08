<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\RegistryDocumentVersion;
use App\Models\Role;
use App\Models\User;
use App\Policies\RegistryDocumentVersionPolicy;
use App\Support\RegistryDocumentApprovalStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RegistryDocumentVersionPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_submit_review_requires_version_management_permission_and_reviewable_state(): void
    {
        $policy = new RegistryDocumentVersionPolicy;
        $manager = $this->userWithPermission('documents.manage_versions');
        $unauthorized = User::factory()->create();
        $version = RegistryDocumentVersion::factory()->create(['approval_status' => 'draft']);

        self::assertTrue($policy->submitReview($manager, $version));
        self::assertFalse($policy->submitReview($unauthorized, $version));

        $version->approval_status = RegistryDocumentApprovalStatus::Rejected;
        self::assertTrue($policy->submitReview($manager, $version));

        $version->approval_status = RegistryDocumentApprovalStatus::PendingReview;
        self::assertFalse($policy->submitReview($manager, $version));
    }

    public function test_approval_and_rejection_require_permission_second_person_and_pending_state(): void
    {
        $policy = new RegistryDocumentVersionPolicy;
        $uploader = $this->userWithPermission('documents.approve');
        $reviewer = $this->userWithPermission('documents.approve');
        $unauthorized = User::factory()->create();
        $version = RegistryDocumentVersion::factory()->create([
            'uploaded_by' => $uploader->id,
            'approval_status' => 'pending_review',
        ]);

        self::assertTrue($policy->approve($reviewer, $version));
        self::assertTrue($policy->reject($reviewer, $version));
        self::assertFalse($policy->approve($unauthorized, $version));
        self::assertFalse($policy->approve($uploader, $version));

        $version->approval_status = RegistryDocumentApprovalStatus::Approved;
        self::assertFalse($policy->approve($reviewer, $version));
        self::assertFalse($policy->reject($reviewer, $version));
    }

    private function userWithPermission(string $permissionName): User
    {
        $permission = Permission::query()->firstOrCreate(
            ['name' => $permissionName],
            ['display_name' => $permissionName],
        );
        $role = Role::query()->create([
            'name' => 'test-role-'.str()->uuid(),
            'display_name' => 'Testrolle',
        ]);
        $role->permissions()->attach($permission);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
