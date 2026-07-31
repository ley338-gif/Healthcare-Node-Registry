<?php

namespace App\Policies;

use App\Models\DiagnosticTestProfile;
use App\Models\User;

final class DiagnosticTestProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('registry.view') || $user->hasPermission('registry.manage');
    }

    public function view(User $user, DiagnosticTestProfile $profile): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('registry.manage');
    }

    public function update(User $user, DiagnosticTestProfile $profile): bool
    {
        return $user->hasPermission('registry.manage');
    }

    public function archive(User $user, DiagnosticTestProfile $profile): bool
    {
        return $user->hasPermission('registry.manage');
    }

    public function execute(User $user, DiagnosticTestProfile $profile): bool
    {
        return $user->hasPermission('registry.manage');
    }
}
