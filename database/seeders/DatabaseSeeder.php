<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect([
            ['name' => 'audit.view', 'display_name' => 'Audit anzeigen'],
            ['name' => 'users.manage', 'display_name' => 'Benutzer verwalten'],
            ['name' => 'roles.manage', 'display_name' => 'Rollen verwalten'],
            ['name' => 'settings.manage', 'display_name' => 'Einstellungen verwalten'],
        ])->map(fn (array $permission): Permission => Permission::query()->firstOrCreate(
            ['name' => $permission['name']],
            $permission,
        ));

        $role = Role::query()->firstOrCreate(
            ['name' => 'system-administrator'],
            ['display_name' => 'System Administrator'],
        );
        $role->permissions()->sync($permissions->modelKeys());

        if (app()->isLocal()) {
            $user = User::query()->firstOrCreate(
                ['email' => 'admin@example.test'],
                [
                    'public_id' => (string) Str::uuid7(),
                    'name' => 'Synthetic Development Admin',
                    'password' => Hash::make('ChangeMe-Development-Only!'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ],
            );
            $user->roles()->syncWithoutDetaching([$role->id]);
        }
    }
}
