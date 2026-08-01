<?php

namespace App\Support;

use App\Models\Permission;
use App\Models\Role;

final class RbacBootstrapper
{
    /** @var array<string,string> */
    private const PERMISSIONS = [
        'audit.view' => 'Audit anzeigen', 'users.manage' => 'Benutzer verwalten', 'roles.manage' => 'Rollen verwalten',
        'settings.manage' => 'Einstellungen verwalten', 'registry.view' => 'Registry anzeigen', 'registry.manage' => 'Registry verwalten',
        'tests.run.storage' => 'DICOM-Storage-Test ausführen',
        'tests.analyze_file' => 'DICOM-Dateien analysieren',
        'tests.export' => 'Diagnoseergebnisse exportieren',
        'documents.view' => 'Dokumente anzeigen',
        'documents.upload' => 'Dokumente hochladen',
        'documents.update' => 'Dokumente bearbeiten',
        'documents.archive' => 'Dokumente archivieren',
        'documents.download' => 'Dokumente herunterladen',
        'documents.manage_versions' => 'Dokumentversionen verwalten',
    ];

    public function ensureSystemAdministratorRole(): Role
    {
        $ids = [];
        foreach (self::PERMISSIONS as $name => $display) {
            $ids[] = Permission::query()->firstOrCreate(['name' => $name], ['display_name' => $display])->id;
        }
        $role = Role::query()->firstOrCreate(['name' => 'system-administrator'], ['display_name' => 'System Administrator']);
        $role->permissions()->syncWithoutDetaching($ids);

        return $role;
    }
}
