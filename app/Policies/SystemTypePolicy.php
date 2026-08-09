<?php

namespace App\Policies;

use App\Models\SystemType;
use App\Models\User;

final class SystemTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('settings.manage');
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, SystemType $systemType): bool
    {
        return $this->viewAny($user);
    }
}
