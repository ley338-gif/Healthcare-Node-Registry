<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\Role;

final class RbacBootstrapper
{
    /** @var array<string, string> */
    private const PERMISSIONS = [
        'audit.view' => 'Audit anzeigen',
        'users.manage' => 'Benutzer verwalten',
        'roles.manage' => 'Rollen verwalten',
        'settings.manage' => 'Einstellungen verwalten',
    ];

    public function ensureSystemAdministratorRole(): Role
    {
        $permissionIds = [];

        foreach (self::PERMISSIONS as $name => $displayName) {
            $permission = Permission::query()->firstOrCreate(
                ['name' => $name],
                ['display_name' => $displayName],
            );
            $permissionIds[] = $permission->id;
        }

        $role = Role::query()->firstOrCreate(
            ['name' => 'system-administrator'],
            ['display_name' => 'System Administrator'],
        );
        $role->permissions()->syncWithoutDetaching($permissionIds);

        return $role;
    }
}
