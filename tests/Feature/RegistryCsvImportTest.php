<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

final class RegistryCsvImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_system_csv_requires_preview_before_import(): void
    {
        $organization = Organization::query()->create(['name' => 'Klinik Nord']);
        $response = $this->actingAs($this->administrator())->post('/systems/import/preview', [
            'kind' => 'systems',
            'csv_file' => UploadedFile::fake()->createWithContent('systems.csv', $this->systemHeader()."\nKlinik Nord,,,PACS Nord,pacs,active,pacs01,,10.10.0.10,,,,,,,,,,"),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('systems', 0);
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);
        $token = (string) $query['token'];

        $this->actingAs($this->administrator())->post("/systems/import/{$token}")->assertRedirect('/systems');
        $this->assertDatabaseHas('systems', ['organization_id' => $organization->id, 'name' => 'PACS Nord']);
        $this->assertDatabaseHas('security_events', ['event_type' => 'registry.csv_import.completed']);
    }

    public function test_invalid_csv_headers_are_reported_without_writes(): void
    {
        $this->actingAs($this->administrator())->from('/systems/import')->post('/systems/import/preview', [
            'kind' => 'systems',
            'csv_file' => UploadedFile::fake()->createWithContent('invalid.csv', "name,status\nPACS,active"),
        ])->assertRedirect('/systems/import')->assertSessionHasErrors('csv_file');

        $this->assertDatabaseCount('systems', 0);
    }

    public function test_dicom_node_duplicate_is_visible_as_invalid_preview(): void
    {
        $organization = Organization::query()->create(['name' => 'Klinik Nord']);
        $system = System::factory()->create(['organization_id' => $organization->id, 'name' => 'PACS Nord']);
        $system->dicomNodes()->create([
            'name' => 'PACS', 'ae_title' => 'PACS_AE', 'host' => '10.10.0.10', 'port' => 104,
            'role' => 'both', 'status' => 'active', 'tls_enabled' => false, 'supports_echo' => true,
            'supports_store' => true, 'supports_query' => true, 'supports_retrieve' => true,
            'supports_storage_commitment' => false, 'supports_mpps' => false, 'supports_worklist' => false,
        ]);
        $csv = $this->nodeHeader()."\nKlinik Nord,PACS Nord,PACS,PACS_AE,10.10.0.10,104,both,active,false,true,true,true,true,false,false,false,,";
        $response = $this->actingAs($this->administrator())->post('/systems/import/preview', ['kind' => 'dicom_nodes', 'csv_file' => UploadedFile::fake()->createWithContent('nodes.csv', $csv)]);
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);

        $response = $this->actingAs($this->administrator())->get('/systems/import?token='.$query['token']);
        $response->assertInertia(fn ($page) => $page->where('preview.valid', 0)->where('preview.invalid', 1)->has('preview.rows.0.errors'));
        $this->assertDatabaseCount('dicom_nodes', 1);
    }

    private function administrator(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());

        return $user;
    }

    private function systemHeader(): string
    {
        return 'organization_name,site_name,department_name,name,system_type,status,hostname,fqdn,ip_address,vendor,product,model,version,operating_system,operating_system_version,serial_number,inventory_number,description,notes';
    }

    private function nodeHeader(): string
    {
        return 'organization_name,system_name,name,ae_title,host,port,role,status,tls_enabled,supports_echo,supports_store,supports_query,supports_retrieve,supports_storage_commitment,supports_mpps,supports_worklist,description,notes';
    }
}
