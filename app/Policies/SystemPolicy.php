<?php

namespace App\Policies;

use App\Models\System;
use App\Models\User;

final class SystemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('registry.view')
            || $user->hasPermission('registry.manage');
    }

    public function view(User $user, System $system): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('registry.manage');
    }

    public function update(User $user, System $system): bool
    {
        return $user->hasPermission('registry.manage');
    }

    public function archive(User $user, System $system): bool
    {
        return $user->hasPermission('registry.manage');
    }
}
