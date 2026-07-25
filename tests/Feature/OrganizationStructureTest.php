<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Organization;
use App\Models\SecurityEvent;
use App\Models\Site;
use App\Models\User;
use App\Support\RbacBootstrapper;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OrganizationStructureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->withoutMiddleware(PreventRequestForgery::class);
        $role = app(RbacBootstrapper::class)->ensureSystemAdministratorRole();
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($role->id);
    }

    public function test_administrator_can_create_complete_structure(): void
    {
        $this->actingAs($this->admin)->post('/organizations', ['name' => 'Synthetischer Klinikverbund', 'short_name' => 'SKV'])->assertSessionHasNoErrors();
        $organization = Organization::query()->firstOrFail();
        $this->actingAs($this->admin)->post('/sites', ['organization_id' => $organization->id, 'name' => 'Teststandort Nord', 'country_code' => 'DE', 'timezone' => 'Europe/Berlin'])->assertSessionHasNoErrors();
        $site = Site::query()->firstOrFail();
        $this->actingAs($this->admin)->post('/departments', ['site_id' => $site->id, 'name' => 'Synthetische Radiologie', 'specialty' => 'Radiologie'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('departments', ['name' => 'Synthetische Radiologie']);
        $this->assertSame(3, SecurityEvent::query()->count());
    }

    public function test_parent_with_active_child_cannot_be_archived(): void
    {
        $organization = Organization::query()->create(['name' => 'Testorganisation']);
        $site = Site::query()->create(['organization_id' => $organization->id, 'name' => 'Teststandort', 'country_code' => 'DE', 'timezone' => 'Europe/Berlin']);
        $this->actingAs($this->admin)->post("/organizations/{$organization->public_id}/archive")->assertSessionHas('error');
        $this->assertNull($organization->fresh()?->archived_at);
        $this->actingAs($this->admin)->post("/sites/{$site->public_id}/archive")->assertSessionHas('success');
        $this->actingAs($this->admin)->post("/organizations/{$organization->public_id}/archive")->assertSessionHas('success');
    }

    public function test_unprivileged_user_is_forbidden(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/organizations')->assertForbidden();
        $this->actingAs($user)->post('/organizations', ['name' => 'Nicht erlaubt'])->assertForbidden();
    }

    public function test_department_archive_is_audited(): void
    {
        $o = Organization::query()->create(['name' => 'Testorganisation']);
        $s = Site::query()->create(['organization_id' => $o->id, 'name' => 'Teststandort', 'country_code' => 'DE', 'timezone' => 'Europe/Berlin']);
        $d = Department::query()->create(['site_id' => $s->id, 'name' => 'Testabteilung']);
        $this->actingAs($this->admin)->post("/departments/{$d->public_id}/archive")->assertSessionHas('success');
        $this->assertDatabaseHas('security_events', ['event_type' => 'registry.department.archived', 'subject_public_id' => $d->public_id]);
    }
}
