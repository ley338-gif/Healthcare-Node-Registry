<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Organization;
use App\Models\RegistryDocument;
use App\Models\RegistryDocumentVersion;
use App\Models\Site;
use App\Models\System;
use App\Models\User;
use App\Support\RbacBootstrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RegistryDocumentIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_index_lists_documents_from_all_registry_contexts(): void
    {
        $this->withoutVite();
        [$organization, $site, $department, $system] = $this->structure();
        foreach ([$organization, $site, $department, $system] as $context) {
            $document = RegistryDocument::factory()->for($context, 'documentable')->create([
                'title' => 'Dokument '.$context->name,
            ]);
            $version = RegistryDocumentVersion::factory()->for($document, 'document')->create();
            $document->update(['current_version_id' => $version->id]);
        }

        $this->actingAs($this->administrator())->get('/documents')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Documents/Index')
                ->has('documents.data', 4)
                ->where('documents.total', 4)
                ->where('documents.data.0.documentable_name', fn (string $name): bool => $name !== '')
                ->where('documents.data.0.documentable_type_label', fn (string $label): bool => in_array($label, ['Organisation', 'Standort', 'Abteilung', 'System'], true))
                ->has('documentCategories')
                ->has('documentUploaders'));
    }

    public function test_document_index_filters_by_registry_context_and_paginates(): void
    {
        $this->withoutVite();
        [$organization, , , $system] = $this->structure();
        RegistryDocument::factory()->count(11)->for($system, 'documentable')->create();
        RegistryDocument::factory()->for($organization, 'documentable')->create();

        $this->actingAs($this->administrator())->get('/documents?document_entity_type=system')
            ->assertInertia(fn ($page) => $page
                ->has('documents.data', 10)
                ->where('documents.total', 11)
                ->where('documentFilters.document_entity_type', 'system')
                ->where('documents.data', fn ($documents) => $documents->every(
                    fn ($document): bool => $document['documentable_type_key'] === 'system',
                )));
        $this->actingAs($this->administrator())->get('/documents?document_entity_type=system&document_page=2')
            ->assertInertia(fn ($page) => $page->has('documents.data', 1));
    }

    public function test_document_index_requires_registry_and_document_view_permissions(): void
    {
        $this->actingAs(User::factory()->create())->get('/documents')->assertForbidden();
    }

    /** @return array{Organization, Site, Department, System} */
    private function structure(): array
    {
        $organization = Organization::query()->create(['name' => 'Dokumentenverbund']);
        $site = Site::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Dokumentenstandort',
            'country_code' => 'DE',
            'timezone' => 'Europe/Berlin',
        ]);
        $department = Department::query()->create(['site_id' => $site->id, 'name' => 'Dokumentenabteilung']);
        $system = System::factory()->create([
            'organization_id' => $organization->id,
            'site_id' => $site->id,
            'department_id' => $department->id,
        ]);

        return [$organization, $site, $department, $system];
    }

    private function administrator(): User
    {
        $role = app(RbacBootstrapper::class)->ensureSystemAdministratorRole();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
