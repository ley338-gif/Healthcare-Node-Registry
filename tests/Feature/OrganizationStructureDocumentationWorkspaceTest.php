<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Organization;
use App\Models\RegistryDocumentation;
use App\Models\Site;
use App\Models\User;
use App\Support\RbacBootstrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OrganizationStructureDocumentationWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private User $administrator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $role = app(RbacBootstrapper::class)->ensureSystemAdministratorRole();
        $this->administrator = User::factory()->create();
        $this->administrator->roles()->attach($role);
    }

    public function test_documentation_is_loaded_for_each_selected_structure_type(): void
    {
        [$organization, $site, $department] = $this->structure();
        $contexts = [
            'organization' => ['route' => 'organizations', 'model' => $organization],
            'site' => ['route' => 'sites', 'model' => $site],
            'department' => ['route' => 'departments', 'model' => $department],
        ];

        foreach ($contexts as $type => $context) {
            $section = "{$type}_description";
            $this->actingAs($this->administrator)
                ->post("/registry-documentation/{$context['route']}/{$context['model']->public_id}", $this->payload($section))
                ->assertSessionHasNoErrors();

            $this->actingAs($this->administrator)
                ->get("/structure?selected_type={$type}&selected_id={$context['model']->public_id}")
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->where('selectedContext.type', $type)
                    ->has('documentation', 1)
                    ->where('documentation.0.section', $section)
                    ->where('canManageDocumentation', true));
        }
    }

    public function test_documentation_is_not_mixed_between_structure_entities(): void
    {
        [$organization, $site] = $this->structure();
        $otherSite = Site::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Zweiter Standort',
            'country_code' => 'DE',
            'timezone' => 'Europe/Berlin',
        ]);
        $this->actingAs($this->administrator)
            ->post("/registry-documentation/sites/{$site->public_id}", $this->payload('description'));
        $this->actingAs($this->administrator)
            ->post("/registry-documentation/sites/{$otherSite->public_id}", $this->payload('description'));

        $this->actingAs($this->administrator)
            ->get("/structure?selected_type=site&selected_id={$site->public_id}")
            ->assertInertia(fn ($page) => $page
                ->has('documentation', 1)
                ->where('documentation.0.documentable_id', $site->id));
    }

    public function test_structure_documentation_edit_is_audited_in_the_same_context(): void
    {
        [$organization] = $this->structure();
        $this->actingAs($this->administrator)
            ->post("/registry-documentation/organizations/{$organization->public_id}", $this->payload('description'));
        $documentation = RegistryDocumentation::query()->firstOrFail();

        $this->actingAs($this->administrator)
            ->put("/registry-documentation/{$documentation->public_id}", [
                ...$this->payload('description'),
                'structured_data' => ['purpose' => 'Aktualisierter Organisationszweck'],
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('security_events', [
            'event_type' => 'documentation.updated',
            'subject_public_id' => $organization->public_id,
        ]);
        $this->actingAs($this->administrator)
            ->get("/structure?selected_type=organization&selected_id={$organization->public_id}&history_type=documentation.updated")
            ->assertInertia(fn ($page) => $page->has('history.data', 2));
    }

    /** @return array{Organization, Site, Department} */
    private function structure(): array
    {
        $organization = Organization::query()->create([
            'name' => 'Dokumentierter Verbund',
            'short_name' => 'DV',
        ]);
        $site = Site::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Hauptstandort',
            'country_code' => 'DE',
            'timezone' => 'Europe/Berlin',
        ]);
        $department = Department::query()->create([
            'site_id' => $site->id,
            'name' => 'Radiologie',
            'specialty' => 'Diagnostische Radiologie',
        ]);

        return [$organization, $site, $department];
    }

    /** @return array<string, mixed> */
    private function payload(string $section): array
    {
        return [
            'documentation_type' => 'operations',
            'section' => $section,
            'title' => 'Beschreibung',
            'content' => null,
            'structured_data' => ['purpose' => 'Dokumentierter Zweck'],
            'visibility' => 'internal',
        ];
    }
}
