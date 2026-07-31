<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DicomNode;
use App\Models\Organization;
use App\Models\SecurityEvent;
use App\Models\Site;
use App\Models\System;
use App\Models\User;
use App\Support\RbacBootstrapper;
use App\Support\RegistryAudit;
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
        $organization = Organization::query()->create(['name' => 'Geschützter Verlauf']);
        $this->actingAs($user)->get('/organizations')->assertForbidden();
        $this->actingAs($user)->post('/organizations', ['name' => 'Nicht erlaubt'])->assertForbidden();
        $this->actingAs($user)
            ->get("/structure?selected_type=organization&selected_id={$organization->public_id}")
            ->assertForbidden();
    }

    public function test_department_archive_is_audited(): void
    {
        $o = Organization::query()->create(['name' => 'Testorganisation']);
        $s = Site::query()->create(['organization_id' => $o->id, 'name' => 'Teststandort', 'country_code' => 'DE', 'timezone' => 'Europe/Berlin']);
        $d = Department::query()->create(['site_id' => $s->id, 'name' => 'Testabteilung']);
        $this->actingAs($this->admin)->post("/departments/{$d->public_id}/archive")->assertSessionHas('success');
        $this->assertDatabaseHas('security_events', ['event_type' => 'registry.department.archived', 'subject_public_id' => $d->public_id]);
    }

    public function test_organization_history_contains_descendants_but_not_foreign_organizations(): void
    {
        [$organization, $site, $department, $system] = $this->structure('Klinik Nord');
        [$foreignOrganization, , , $foreignSystem] = $this->structure('Klinik Süd');
        $node = DicomNode::factory()->create(['system_id' => $system->id]);
        $audit = new RegistryAudit;
        $audit->record('registry.organization.updated', $organization, $this->admin);
        $audit->record('registry.site.updated', $site, $this->admin);
        $audit->record('registry.department.updated', $department, $this->admin);
        $audit->record('diagnostic.echo.completed', $node, $this->admin, ['status' => 'success']);
        $audit->record('registry.system.updated', $foreignSystem, $this->admin);

        $this->actingAs($this->admin)
            ->get("/structure?selected_type=organization&selected_id={$organization->public_id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('selectedContext.type', 'organization')
                ->has('history.data', 4)
                ->where('historyStats.total', 4)
                ->where('history.data', fn ($events) => $events->every(
                    fn (array $event): bool => $event['subject_public_id'] !== $foreignOrganization->public_id
                        && $event['subject_public_id'] !== $foreignSystem->public_id,
                )));
    }

    public function test_site_and_department_history_stay_in_the_selected_context(): void
    {
        [$organization, $site, $department, $system] = $this->structure('Verbund');
        $otherSite = Site::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Anderer Standort',
            'country_code' => 'DE',
            'timezone' => 'Europe/Berlin',
        ]);
        $audit = new RegistryAudit;
        $audit->record('registry.system.updated', $system, $this->admin);
        $audit->record('registry.site.updated', $otherSite, $this->admin);

        $this->actingAs($this->admin)
            ->get("/structure?selected_type=site&selected_id={$site->public_id}")
            ->assertInertia(fn ($page) => $page->has('history.data', 1)->where('history.data.0.subject_public_id', $system->public_id));

        $this->actingAs($this->admin)
            ->get("/structure?selected_type=department&selected_id={$department->public_id}")
            ->assertInertia(fn ($page) => $page->has('history.data', 1)->where('history.data.0.subject_public_id', $system->public_id));
    }

    public function test_history_scope_can_be_limited_to_direct_changes(): void
    {
        [$organization, $site] = $this->structure('Direktfilter');
        $audit = new RegistryAudit;
        $audit->record('registry.organization.updated', $organization, $this->admin);
        $audit->record('registry.site.updated', $site, $this->admin);

        $this->actingAs($this->admin)
            ->get("/structure?selected_type=organization&selected_id={$organization->public_id}&history_scope=direct")
            ->assertInertia(fn ($page) => $page
                ->has('history.data', 1)
                ->where('history.data.0.subject_public_id', $organization->public_id)
                ->where('historyFilters.history_scope', 'direct'));
    }

    /** @return array{Organization, Site, Department, System} */
    private function structure(string $name): array
    {
        $organization = Organization::query()->create(['name' => $name]);
        $site = Site::query()->create([
            'organization_id' => $organization->id,
            'name' => "{$name} Standort",
            'country_code' => 'DE',
            'timezone' => 'Europe/Berlin',
        ]);
        $department = Department::query()->create(['site_id' => $site->id, 'name' => "{$name} Radiologie"]);
        $system = System::factory()->create([
            'organization_id' => $organization->id,
            'site_id' => $site->id,
            'department_id' => $department->id,
        ]);

        return [$organization, $site, $department, $system];
    }
}
