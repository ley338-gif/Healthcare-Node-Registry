<?php

namespace App\Services\Identity;

use App\Models\Role;
use App\Models\User;
use App\Support\RegistryAudit;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class RoleAdministrationService
{
    public function __construct(private readonly RegistryAudit $audit) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $permissionIds
     */
    public function create(array $data, array $permissionIds, User $actor): Role
    {
        return DB::transaction(function () use ($data, $permissionIds, $actor): Role {
            $role = Role::query()->create(['name' => $data['name'], 'display_name' => $data['display_name']]);
            $role->permissions()->sync($permissionIds);
            $this->audit->record('identity.role.created', $role, $actor, ['name' => $role->name, 'permission_ids' => $permissionIds]);

            return $role;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $permissionIds
     */
    public function update(Role $role, array $data, array $permissionIds, User $actor): void
    {
        $this->assertMutable($role);
        DB::transaction(function () use ($role, $data, $permissionIds, $actor): void {
            $before = ['name' => $role->name, 'display_name' => $role->display_name, 'permission_ids' => $role->permissions()->pluck('permissions.id')->all()];
            $role->update(['name' => $data['name'], 'display_name' => $data['display_name']]);
            $role->permissions()->sync($permissionIds);
            $after = ['name' => $role->name, 'display_name' => $role->display_name, 'permission_ids' => $permissionIds];
            $this->audit->record('identity.role.updated', $role, $actor, ['changed_fields' => array_keys($after), 'before' => $before, 'after' => $after]);
        });
    }

    public function delete(Role $role, User $actor): void
    {
        $this->assertMutable($role);
        if ($role->users()->exists()) {
            throw ValidationException::withMessages(['role' => 'Die Rolle ist noch Benutzern zugewiesen.']);
        }
        DB::transaction(function () use ($role, $actor): void {
            $this->audit->record('identity.role.deleted', $role, $actor, ['name' => $role->name]);
            $role->delete();
        });
    }

    private function assertMutable(Role $role): void
    {
        if ($role->name === 'system-administrator') {
            throw ValidationException::withMessages(['role' => 'Die Systemadministrator-Rolle ist geschützt.']);
        }
    }
}
