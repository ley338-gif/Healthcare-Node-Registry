<?php

namespace App\Policies;

use App\Models\SystemStatus;
use App\Models\User;

final class SystemStatusPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('settings.manage');
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, SystemStatus $systemStatus): bool
    {
        return $this->viewAny($user);
    }
}
