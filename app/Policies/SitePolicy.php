<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

final class SitePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('registry.view') || $user->hasPermission('registry.manage');
    }

    public function view(User $user, Site $model): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('registry.manage');
    }

    public function update(User $user, Site $model): bool
    {
        return $user->hasPermission('registry.manage');
    }

    public function archive(User $user, Site $model): bool
    {
        return $user->hasPermission('registry.manage');
    }
}
