<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

final class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('registry.view') || $user->hasPermission('registry.manage');
    }

    public function view(User $user, Organization $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('registry.manage');
    }

    public function update(User $user, Organization $model): bool
    {
        return $user->hasPermission('registry.manage');
    }

    public function archive(User $user, Organization $model): bool
    {
        return $user->hasPermission('registry.manage');
    }
}
