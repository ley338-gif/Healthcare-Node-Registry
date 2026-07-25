<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\RbacBootstrapper;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class DatabaseSeeder extends Seeder
{
    public function run(RbacBootstrapper $rbac): void
    {
        $role = $rbac->ensureSystemAdministratorRole();

        if (! app()->isLocal()) {
            return;
        }

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
