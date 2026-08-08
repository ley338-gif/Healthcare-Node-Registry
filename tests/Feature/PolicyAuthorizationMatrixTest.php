<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DiagnosticTestProfile;
use App\Models\DiagnosticTestRun;
use App\Models\DicomConnection;
use App\Models\DicomNode;
use App\Models\DiscoveredHost;
use App\Models\DiscoveryAllowedNetwork;
use App\Models\DiscoveryRun;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Site;
use App\Models\System;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class PolicyAuthorizationMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_policy_allows_its_permissions_and_returns_403_without_them(): void
    {
        $this->seed();

        foreach ($this->policies() as $name => $configuration) {
            $allowed = $this->userWithPermissions($name, $configuration['permissions']);
            $denied = User::factory()->create();
            $modelClass = $configuration['model']::class;
            $subject = $configuration['model'];

            foreach ($configuration['abilities'] as $ability) {
                $target = in_array($ability, ['viewAny', 'create'], true) ? $modelClass : $subject;
                self::assertTrue(
                    Gate::forUser($allowed)->allows($ability, $target),
                    "{$name} must allow {$ability} with the documented permissions.",
                );
                self::assertFalse(
                    Gate::forUser($denied)->allows($ability, $target),
                    "{$name} must deny {$ability} without permissions.",
                );
            }

            $uri = '/_policy-probe/'.strtolower($name);
            Route::middleware('web')->get($uri, static function () use ($modelClass) {
                Gate::authorize('viewAny', $modelClass);

                return response()->noContent();
            });

            $this->actingAs($allowed)->get($uri)->assertNoContent();
            $this->actingAs($denied)->get($uri)->assertForbidden();
        }
    }

    /** @return array<string, array{model: Model, abilities: list<string>, permissions: list<string>}> */
    private function policies(): array
    {
        return [
            'DepartmentPolicy' => [
                'model' => new Department,
                'abilities' => ['viewAny', 'view', 'create', 'update', 'archive'],
                'permissions' => ['registry.view', 'registry.manage'],
            ],
            'DiagnosticTestProfilePolicy' => [
                'model' => new DiagnosticTestProfile(['test_type' => 'network']),
                'abilities' => ['viewAny', 'view', 'create', 'update', 'archive', 'execute'],
                'permissions' => ['registry.view', 'registry.manage', 'diagnostics.echo'],
            ],
            'DiagnosticTestRunPolicy' => [
                'model' => new DiagnosticTestRun,
                'abilities' => ['viewAny', 'view'],
                'permissions' => ['registry.view'],
            ],
            'DicomConnectionPolicy' => [
                'model' => new DicomConnection,
                'abilities' => ['viewAny', 'view', 'create', 'update', 'archive'],
                'permissions' => ['registry.view', 'registry.manage'],
            ],
            'DicomNodePolicy' => [
                'model' => new DicomNode,
                'abilities' => ['viewAny', 'view', 'create', 'update', 'archive', 'verify'],
                'permissions' => ['registry.view', 'registry.manage', 'diagnostics.echo'],
            ],
            'DiscoveredHostPolicy' => [
                'model' => new DiscoveredHost,
                'abilities' => ['viewAny', 'view', 'review', 'promote'],
                'permissions' => ['discovery.view', 'discovery.manage', 'registry.manage'],
            ],
            'DiscoveryAllowedNetworkPolicy' => [
                'model' => new DiscoveryAllowedNetwork,
                'abilities' => ['viewAny', 'create', 'update', 'delete'],
                'permissions' => ['discovery.manage'],
            ],
            'DiscoveryRunPolicy' => [
                'model' => new DiscoveryRun,
                'abilities' => ['viewAny', 'view', 'create', 'cancel'],
                'permissions' => ['discovery.view', 'discovery.run'],
            ],
            'OrganizationPolicy' => [
                'model' => new Organization,
                'abilities' => ['viewAny', 'view', 'create', 'update', 'archive'],
                'permissions' => ['registry.view', 'registry.manage'],
            ],
            'RolePolicy' => [
                'model' => new Role,
                'abilities' => ['viewAny', 'view', 'create', 'update', 'delete'],
                'permissions' => ['roles.manage'],
            ],
            'SitePolicy' => [
                'model' => new Site,
                'abilities' => ['viewAny', 'view', 'create', 'update', 'archive'],
                'permissions' => ['registry.view', 'registry.manage'],
            ],
            'SystemPolicy' => [
                'model' => new System,
                'abilities' => ['viewAny', 'view', 'create', 'update', 'archive'],
                'permissions' => ['registry.view', 'registry.manage'],
            ],
            'UserPolicy' => [
                'model' => new User,
                'abilities' => ['viewAny', 'view', 'create', 'update', 'resetPassword'],
                'permissions' => ['users.manage'],
            ],
        ];
    }

    /** @param list<string> $permissionNames */
    private function userWithPermissions(string $name, array $permissionNames): User
    {
        $role = Role::query()->create([
            'name' => strtolower($name).'-'.uniqid(),
            'display_name' => $name,
        ]);
        $role->permissions()->sync(
            Permission::query()->whereIn('name', $permissionNames)->pluck('id'),
        );
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
