<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OrganizationStructureOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_structure_overview(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $organization = Organization::query()->create(['name' => 'Testorganisation']);
        $site = Site::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Teststandort',
            'country_code' => 'DE',
            'timezone' => 'Europe/Berlin',
        ]);
        Department::query()->create(['site_id' => $site->id, 'name' => 'Testabteilung']);

        $this->actingAs($user)
            ->get('/structure')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('OrganizationStructure/Index')
                ->where('summary.organizations', 1)
                ->where('summary.sites', 1)
                ->where('summary.departments', 1)
                ->has('recentOrganizations', 1)
                ->has('recentSites', 1)
                ->has('recentDepartments', 1)
            );
    }
}
