<?php

namespace App\Policies;

use App\Models\RegistryDocumentVersion;
use App\Models\User;
use App\Support\RegistryDocumentApprovalStatus;

final class RegistryDocumentVersionPolicy
{
    public function submitReview(User $user, RegistryDocumentVersion $version): bool
    {
        return $user->hasPermission('documents.manage_versions')
            && in_array($version->approval_status, [RegistryDocumentApprovalStatus::Draft, RegistryDocumentApprovalStatus::Rejected], true);
    }

    public function approve(User $user, RegistryDocumentVersion $version): bool
    {
        return $user->hasPermission('documents.approve')
            && $user->id !== $version->uploaded_by
            && $version->approval_status === RegistryDocumentApprovalStatus::PendingReview;
    }

    public function reject(User $user, RegistryDocumentVersion $version): bool
    {
        return $this->approve($user, $version);
    }
}
