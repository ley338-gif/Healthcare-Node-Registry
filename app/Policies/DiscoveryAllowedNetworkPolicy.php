<?php

namespace App\Policies;

use App\Models\DiscoveryAllowedNetwork;
use App\Models\User;

final class DiscoveryAllowedNetworkPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('discovery.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('discovery.manage');
    }

    public function update(User $user, DiscoveryAllowedNetwork $discoveryAllowedNetwork): bool
    {
        return $user->hasPermission('discovery.manage');
    }

    public function delete(User $user, DiscoveryAllowedNetwork $discoveryAllowedNetwork): bool
    {
        return $user->hasPermission('discovery.manage');
    }
}
