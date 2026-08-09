<?php

namespace App\Policies;

use App\Models\Manufacturer;
use App\Models\User;

final class ManufacturerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('settings.manage');
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Manufacturer $manufacturer): bool
    {
        return $this->viewAny($user);
    }
}
