<?php

namespace App\Policies;

use App\Models\DicomConnection;
use App\Models\User;

final class DicomConnectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('registry.view')
            || $user->hasPermission('registry.manage');
    }

    public function view(
        User $user,
        DicomConnection $dicomConnection,
    ): bool {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('registry.manage');
    }

    public function update(
        User $user,
        DicomConnection $dicomConnection,
    ): bool {
        return $user->hasPermission('registry.manage');
    }

    public function archive(
        User $user,
        DicomConnection $dicomConnection,
    ): bool {
        return $user->hasPermission('registry.manage');
    }
}
