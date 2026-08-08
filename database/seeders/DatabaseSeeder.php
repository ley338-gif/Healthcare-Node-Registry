<?php

namespace Database\Seeders;

use App\Models\DiscoveryAllowedNetwork;
use App\Models\Permission;
use App\Models\Role;
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
        $rbac->ensureDiagnosticRoles();
        $this->ensureViewerRole();
        $this->ensureDefaultAllowedNetworks();

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

        $this->call(ExampleRegistryDataSeeder::class);
    }

    /**
     * Standardrolle "Benutzer": Discovery-Läufe ansehen, Ergebnisse prüfen,
     * Systeme und Topologie ansehen. Das Recht, eigene Discovery-Läufe zu
     * starten, ist bewusst NICHT enthalten und muss ein Administrator
     * gezielt über Einstellungen > Rollen zuweisen (discovery.run).
     */
    private function ensureViewerRole(): void
    {
        $permissionNames = ['discovery.view', 'registry.view', 'documents.view'];
        $ids = Permission::query()->whereIn('name', $permissionNames)->pluck('id');
        $role = Role::query()->firstOrCreate(['name' => 'user'], ['display_name' => 'Benutzer']);
        $role->permissions()->syncWithoutDetaching($ids);
    }

    /**
     * Konservative Standardfreigabe: nur private (RFC1918) Netzbereiche.
     * Administratoren können dies unter Einstellungen > Discovery anpassen.
     */
    private function ensureDefaultAllowedNetworks(): void
    {
        foreach ([
            ['cidr' => '10.0.0.0/8', 'description' => 'RFC1918 privates Netz (Klasse A)'],
            ['cidr' => '172.16.0.0/12', 'description' => 'RFC1918 privates Netz (Klasse B)'],
            ['cidr' => '192.168.0.0/16', 'description' => 'RFC1918 privates Netz (Klasse C)'],
        ] as $network) {
            DiscoveryAllowedNetwork::query()->firstOrCreate(
                ['cidr' => $network['cidr']],
                ['description' => $network['description'], 'active' => true],
            );
        }
    }
}
