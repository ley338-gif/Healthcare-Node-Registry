<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

final class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('registry.view') || $user->hasPermission('registry.manage');
    }

    public function view(User $user, Department $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('registry.manage');
    }

    public function update(User $user, Department $model): bool
    {
        return $user->hasPermission('registry.manage');
    }

    public function archive(User $user, Department $model): bool
    {
        return $user->hasPermission('registry.manage');
    }
}
