<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Organization;
use App\Models\RegistryDocumentation;
use App\Models\SecurityEvent;
use App\Models\Site;
use App\Models\System;
use App\Models\User;
use App\Support\RbacBootstrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RegistryDocumentationTest extends TestCase
{
    use RefreshDatabase;

    private User $administrator;

    protected function setUp(): void
    {
        parent::setUp();
        $role = app(RbacBootstrapper::class)->ensureSystemAdministratorRole();
        $this->administrator = User::factory()->create();
        $this->administrator->roles()->attach($role);
    }

    public function test_documentation_can_be_created_read_and_assigned_to_each_registry_entity(): void
    {
        [$organization, $site, $department, $system] = $this->structure();
        $contexts = [
            'organizations' => $organization,
            'sites' => $site,
            'departments' => $department,
            'systems' => $system,
        ];

        foreach ($contexts as $type => $context) {
            $this->actingAs($this->administrator)
                ->post("/registry-documentation/{$type}/{$context->public_id}", $this->payload("{$type}-overview"))
                ->assertRedirect()
                ->assertSessionHasNoErrors();

            $this->actingAs($this->administrator)
                ->getJson("/registry-documentation/{$type}/{$context->public_id}")
                ->assertOk()
                ->assertJsonPath('data.0.section', "{$type}-overview");
        }

        $this->assertSame(4, RegistryDocumentation::query()->count());
        foreach ($contexts as $context) {
            $this->assertSame(1, $context->documentation()->count());
        }
    }

    public function test_documentation_update_is_audited_without_raw_content(): void
    {
        [, , , $system] = $this->structure();
        $secretContent = 'Interne Betriebsbeschreibung mit vertraulichem Kontakt';
        $this->actingAs($this->administrator)
            ->post("/registry-documentation/systems/{$system->public_id}", $this->payload('operations'));
        $documentation = RegistryDocumentation::query()->firstOrFail();

        $this->actingAs($this->administrator)
            ->put("/registry-documentation/{$documentation->public_id}", [
                ...$this->payload('operations'),
                'title' => 'Aktualisierter Betrieb',
                'content' => $secretContent,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('Aktualisierter Betrieb', $documentation->fresh()?->title);
        $event = SecurityEvent::query()->latest('occurred_at')->firstOrFail();
        $this->assertSame('documentation.updated', $event->event_type);
        $this->assertSame($system->public_id, $event->subject_public_id);
        $this->assertSame('operations', $event->metadata['section']);
        $this->assertContains('content', $event->metadata['changed_fields']);
        $this->assertArrayHasKey('sha256', $event->metadata['after']['content']);
        $this->assertStringNotContainsString($secretContent, json_encode($event->metadata, JSON_THROW_ON_ERROR));
    }

    public function test_documentation_requires_existing_registry_permissions(): void
    {
        [, , , $system] = $this->structure();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson("/registry-documentation/systems/{$system->public_id}")
            ->assertForbidden();
        $this->actingAs($user)
            ->post("/registry-documentation/systems/{$system->public_id}", $this->payload('security'))
            ->assertForbidden();
        $this->assertDatabaseCount('registry_documentation', 0);
    }

    /** @return array{Organization, Site, Department, System} */
    private function structure(): array
    {
        $organization = Organization::query()->create(['name' => 'Dokumentationsverbund']);
        $site = Site::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Dokumentationsstandort',
            'country_code' => 'DE',
            'timezone' => 'Europe/Berlin',
        ]);
        $department = Department::query()->create(['site_id' => $site->id, 'name' => 'Dokumentationsabteilung']);
        $system = System::factory()->create([
            'organization_id' => $organization->id,
            'site_id' => $site->id,
            'department_id' => $department->id,
        ]);

        return [$organization, $site, $department, $system];
    }

    /** @return array<string, mixed> */
    private function payload(string $section): array
    {
        return [
            'documentation_type' => 'operations',
            'section' => $section,
            'title' => 'Betriebsdokumentation',
            'content' => 'Technische Betriebsinformationen',
            'structured_data' => ['monitoring' => true],
            'visibility' => 'internal',
        ];
    }
}
