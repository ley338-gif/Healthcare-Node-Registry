<?php

namespace Tests\Feature;

use App\Models\RegistryDocument;
use App\Models\RegistryDocumentVersion;
use App\Models\System;
use App\Models\User;
use App\Support\RbacBootstrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RegistryDocumentSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_document_search_and_filters_are_server_side_and_context_scoped(): void
    {
        $this->withoutVite();
        $system = System::factory()->create();
        $foreignSystem = System::factory()->create();
        $uploader = User::factory()->create(['name' => 'Dokument Uploader']);
        $document = RegistryDocument::factory()->for($system, 'documentable')->create([
            'title' => 'Firewall Freigabe',
            'description' => 'Kommunikation zum PACS',
            'category' => 'firewall_approval',
            'contract_reference' => 'FW-4711',
            'tags' => ['netzwerk', 'pacs'],
            'valid_until' => now()->addDays(30)->toDateString(),
        ]);
        $version = RegistryDocumentVersion::factory()->for($document, 'document')->create([
            'original_filename' => 'pacs-firewall.pdf',
            'file_extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'malware_scan_status' => 'clean',
            'uploaded_by' => $uploader->id,
            'uploaded_at' => now(),
        ]);
        $document->update(['current_version_id' => $version->id]);
        RegistryDocument::factory()->for($foreignSystem, 'documentable')->create(['title' => 'pacs-firewall fremd']);

        $query = http_build_query([
            'document_search' => 'pacs-firewall',
            'document_category' => 'firewall_approval',
            'document_file_type' => 'pdf',
            'document_status' => 'active',
            'document_validity' => 'expiring_soon',
            'document_uploader' => $uploader->public_id,
            'document_from' => now()->subDay()->toDateString(),
            'document_to' => now()->addDay()->toDateString(),
            'document_scan_status' => 'clean',
        ]);

        $this->actingAs($this->administrator())->get("/systems/{$system->public_id}?{$query}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('documents.data', 1)
                ->where('documents.data.0.public_id', $document->public_id)
                ->where('documentFilters.document_search', 'pacs-firewall')
                ->where('documentUploaders.0.public_id', $uploader->public_id));
    }

    public function test_document_results_are_paginated_ten_per_page(): void
    {
        $this->withoutVite();
        $system = System::factory()->create();
        foreach (range(1, 12) as $number) {
            RegistryDocument::factory()->for($system, 'documentable')->create(['title' => "Dokument {$number}"]);
        }

        $this->actingAs($this->administrator())->get("/systems/{$system->public_id}")
            ->assertInertia(fn ($page) => $page
                ->has('documents.data', 10)
                ->where('documents.total', 12)
                ->has('documents.links'));
        $this->actingAs($this->administrator())->get("/systems/{$system->public_id}?document_page=2")
            ->assertInertia(fn ($page) => $page->has('documents.data', 2));
    }

    private function administrator(): User
    {
        $role = app(RbacBootstrapper::class)->ensureSystemAdministratorRole();
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }
}
