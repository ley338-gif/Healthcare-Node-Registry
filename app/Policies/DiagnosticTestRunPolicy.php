<?php

namespace App\Policies;

use App\Models\DiagnosticTestRun;
use App\Models\User;

final class DiagnosticTestRunPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('registry.view')
            || $user->hasPermission('registry.manage');
    }

    public function view(User $user, DiagnosticTestRun $run): bool
    {
        return $this->viewAny($user);
    }
}
