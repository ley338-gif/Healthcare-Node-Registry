<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** @var array<string, string> */
    private const PERMISSIONS = [
        'diagnostics.echo' => 'DICOM C-ECHO und Netzwerkdiagnose ausführen',
        'diagnostics.worklist' => 'DICOM Worklist-Abfrage ausführen',
        'diagnostics.query' => 'DICOM C-FIND-Abfrage ausführen',
        'diagnostics.store' => 'DICOM C-STORE-Test ausführen',
        'diagnostics.move' => 'DICOM C-MOVE-Test ausführen',
        'diagnostics.get' => 'DICOM C-GET-Test ausführen',
        'diagnostics.mpps' => 'DICOM MPPS-Test ausführen',
        'diagnostics.storage_commitment' => 'DICOM Storage Commitment testen',
        'diagnostics.capability_matrix' => 'DICOM Capability-Matrix prüfen',
    ];

    public function up(): void
    {
        $now = now();
        foreach (self::PERMISSIONS as $name => $displayName) {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name],
                ['display_name' => $displayName, 'updated_at' => $now, 'created_at' => $now],
            );
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_keys(self::PERMISSIONS))
            ->pluck('id', 'name');

        $this->inheritPermissions(
            'registry.manage',
            [
                'diagnostics.echo',
                'diagnostics.worklist',
                'diagnostics.query',
                'diagnostics.move',
                'diagnostics.get',
                'diagnostics.mpps',
            ],
            $permissionIds->all(),
            $now,
        );
        $this->inheritPermissions(
            'tests.run.storage',
            [
                'diagnostics.store',
                'diagnostics.storage_commitment',
                'diagnostics.capability_matrix',
            ],
            $permissionIds->all(),
            $now,
        );

        $this->ensureRole(
            'pacs-administrator',
            'PACS-Administrator',
            [
                'registry.view',
                'tests.analyze_file',
                'tests.export',
                ...array_keys(self::PERMISSIONS),
            ],
            $now,
        );
        $this->ensureRole(
            'read-only',
            'Nur Lesen',
            ['registry.view', 'documents.view', 'discovery.view'],
            $now,
        );
    }

    public function down(): void
    {
        $roleIds = DB::table('roles')
            ->whereIn('name', ['pacs-administrator', 'read-only'])
            ->pluck('id');
        DB::table('role_user')->whereIn('role_id', $roleIds)->delete();
        DB::table('permission_role')->whereIn('role_id', $roleIds)->delete();
        DB::table('roles')->whereIn('id', $roleIds)->delete();

        $permissionIds = DB::table('permissions')
            ->whereIn('name', array_keys(self::PERMISSIONS))
            ->pluck('id');
        DB::table('permission_role')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }

    /**
     * @param  list<string>  $permissions
     * @param  array<string, int>  $permissionIds
     */
    private function inheritPermissions(string $legacyPermission, array $permissions, array $permissionIds, mixed $now): void
    {
        $legacyPermissionId = DB::table('permissions')->where('name', $legacyPermission)->value('id');
        if ($legacyPermissionId === null) {
            return;
        }

        $roleIds = DB::table('permission_role')->where('permission_id', $legacyPermissionId)->pluck('role_id');
        foreach ($roleIds as $roleId) {
            foreach ($permissions as $permission) {
                DB::table('permission_role')->updateOrInsert(
                    ['permission_id' => $permissionIds[$permission], 'role_id' => $roleId],
                    ['updated_at' => $now, 'created_at' => $now],
                );
            }
        }
    }

    /** @param list<string> $permissionNames */
    private function ensureRole(string $name, string $displayName, array $permissionNames, mixed $now): void
    {
        DB::table('roles')->updateOrInsert(
            ['name' => $name],
            ['display_name' => $displayName, 'updated_at' => $now, 'created_at' => $now],
        );
        $roleId = DB::table('roles')->where('name', $name)->value('id');
        $permissionIds = DB::table('permissions')->whereIn('name', $permissionNames)->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('permission_role')->updateOrInsert(
                ['permission_id' => $permissionId, 'role_id' => $roleId],
                ['updated_at' => $now, 'created_at' => $now],
            );
        }
    }
};
