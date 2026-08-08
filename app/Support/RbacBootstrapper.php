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
        'discovery.view' => 'Discovery anzeigen',
        'discovery.run' => 'Discovery-Läufe starten',
        'discovery.manage' => 'Discovery verwalten',
    ];

    public function ensureSystemAdministratorRole(): Role
    {
        $ids = [];
        foreach ([...self::PERMISSIONS, ...DiagnosticPermission::labels()] as $name => $display) {
            $ids[] = Permission::query()->firstOrCreate(['name' => $name], ['display_name' => $display])->id;
        }
        $role = Role::query()->firstOrCreate(['name' => 'system-administrator'], ['display_name' => 'System Administrator']);
        $role->permissions()->syncWithoutDetaching($ids);

        return $role;
    }

    public function ensureDiagnosticRoles(): void
    {
        $pacsPermissionNames = [
            'registry.view',
            'tests.analyze_file',
            'tests.export',
            ...array_column(DiagnosticPermission::cases(), 'value'),
        ];
        $diagnosticPermissionIds = Permission::query()->whereIn('name', $pacsPermissionNames)->pluck('id');
        $pacsAdministrator = Role::query()->firstOrCreate(
            ['name' => 'pacs-administrator'],
            ['display_name' => 'PACS-Administrator'],
        );
        $pacsAdministrator->permissions()->syncWithoutDetaching($diagnosticPermissionIds);

        $readOnly = Role::query()->firstOrCreate(
            ['name' => 'read-only'],
            ['display_name' => 'Nur Lesen'],
        );
        $readOnly->permissions()->syncWithoutDetaching(
            Permission::query()->whereIn('name', ['registry.view', 'documents.view', 'discovery.view'])->pluck('id'),
        );
    }
}
