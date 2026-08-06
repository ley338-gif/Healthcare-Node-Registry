<?php

namespace App\Policies;

use App\Models\DiscoveryRun;
use App\Models\User;

final class DiscoveryRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('discovery.view')
            || $user->hasPermission('discovery.run')
            || $user->hasPermission('discovery.manage');
    }

    public function view(User $user, DiscoveryRun $discoveryRun): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('discovery.run')
            || $user->hasPermission('discovery.manage');
    }

    public function cancel(User $user, DiscoveryRun $discoveryRun): bool
    {
        return $this->create($user);
    }
}
