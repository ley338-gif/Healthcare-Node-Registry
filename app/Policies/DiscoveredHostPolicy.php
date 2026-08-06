<?php

namespace App\Policies;

use App\Models\DiscoveredHost;
use App\Models\User;

final class DiscoveredHostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('discovery.view')
            || $user->hasPermission('discovery.run')
            || $user->hasPermission('discovery.manage');
    }

    public function view(User $user, DiscoveredHost $discoveredHost): bool
    {
        return $this->viewAny($user);
    }

    public function review(User $user, DiscoveredHost $discoveredHost): bool
    {
        return $user->hasPermission('discovery.manage');
    }

    public function promote(User $user, DiscoveredHost $discoveredHost): bool
    {
        return $user->hasPermission('discovery.manage')
            && $user->hasPermission('registry.manage');
    }
}
