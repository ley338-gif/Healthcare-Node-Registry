<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Organization;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_registry_counts(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();
        $o = Organization::query()->create(['name' => 'Testorganisation']);
        $s = Site::query()->create(['organization_id' => $o->id, 'name' => 'Teststandort', 'country_code' => 'DE', 'timezone' => 'Europe/Berlin']);
        Department::query()->create(['site_id' => $s->id, 'name' => 'Testabteilung']);
        $this->actingAs($user)->get('/')->assertOk()->assertInertia(fn ($page) => $page->component('Dashboard')->where('summary.organizations', 1)->where('summary.sites', 1)->where('summary.departments', 1)->where('summary.systems', 0));
    }
}
