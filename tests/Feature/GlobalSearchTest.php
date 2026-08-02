<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\RegistryDocument;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use App\Support\RbacBootstrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(RbacBootstrapper::class)->ensureSystemAdministratorRole();
    }

    public function test_global_search_returns_permission_checked_cross_workspace_results(): void
    {
        $administrator = User::factory()->create();
        $administrator->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());
        $system = System::factory()->create(['name' => 'Cardio Archive SearchTarget']);
        $document = RegistryDocument::factory()->create([
            'documentable_id' => $system->id,
            'title' => 'SearchTarget Betriebsanleitung',
        ]);
        $managedUser = User::factory()->create(['name' => 'SearchTarget Operator']);

        $response = $this->actingAs($administrator)->getJson('/search?q=SearchTarget')->assertOk();

        $response->assertJsonFragment([
            'type' => 'System',
            'title' => $system->name,
            'url' => "/systems/{$system->public_id}",
        ])->assertJsonFragment([
            'type' => 'Dokument',
            'title' => $document->title,
            'url' => "/documents?document={$document->public_id}",
        ])->assertJsonFragment([
            'type' => 'Benutzer',
            'title' => $managedUser->name,
        ]);
    }

    public function test_search_does_not_expose_results_without_their_permissions(): void
    {
        $user = User::factory()->create();
        $role = Role::query()->create(['name' => 'registry-reader', 'display_name' => 'Registry Reader']);
        $role->permissions()->attach(Permission::query()->where('name', 'registry.view')->firstOrFail());
        $user->roles()->attach($role);
        System::factory()->create(['name' => 'Restricted SearchTarget System']);
        RegistryDocument::factory()->create(['title' => 'Restricted SearchTarget Document']);
        User::factory()->create(['name' => 'Restricted SearchTarget User']);

        $results = $this->actingAs($user)->getJson('/search?q=SearchTarget')->assertOk()->json('results');

        self::assertContains('System', array_column($results, 'type'));
        self::assertNotContains('Dokument', array_column($results, 'type'));
        self::assertNotContains('Benutzer', array_column($results, 'type'));
    }

    public function test_search_requires_authentication_and_at_least_two_characters(): void
    {
        $this->getJson('/search?q=registry')->assertUnauthorized();
        $this->actingAs(User::factory()->create())->getJson('/search?q=a')->assertUnprocessable();
    }
}
