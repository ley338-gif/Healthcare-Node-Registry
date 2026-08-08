<?php

namespace App\Policies;

use App\Models\DicomNode;
use App\Models\User;
use App\Support\DiagnosticPermission;

final class DicomNodePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('registry.view')
            || $user->hasPermission('registry.manage');
    }

    public function view(User $user, DicomNode $dicomNode): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('registry.manage');
    }

    public function update(User $user, DicomNode $dicomNode): bool
    {
        return $user->hasPermission('registry.manage');
    }

    public function archive(User $user, DicomNode $dicomNode): bool
    {
        return $user->hasPermission('registry.manage');
    }

    public function verify(User $user, DicomNode $dicomNode): bool
    {
        return $this->view($user, $dicomNode) && DiagnosticPermission::Echo->allows($user);
    }
}
