<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Organization;
use App\Models\Site;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SystemModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_can_be_created_with_organizational_assignment(): void
    {
        $organization = Organization::query()->create([
            'name' => 'Musterklinik',
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
        ]);

        $system = System::query()->create([
            'organization_id' => $organization->id,
            'site_id' => $site->id,
            'department_id' => $department->id,
            'name' => 'PACS Produktion',
            'system_type' => 'pacs',
            'status' => 'active',
            'hostname' => 'pacs01',
            'fqdn' => 'pacs01.example.local',
            'ip_address' => '10.10.10.20',
            'vendor' => 'VISUS',
            'product' => 'JiveX',
        ]);

        $this->assertNotNull($system->public_id);
        $this->assertSame('PACS Produktion', $system->name);
        $this->assertTrue($system->organization->is($organization));
        $this->assertTrue($system->site?->is($site));
        $this->assertTrue($system->department?->is($department));
        $this->assertNull($system->archived_at);
    }

    public function test_active_scope_excludes_archived_systems(): void
    {
        $organization = Organization::query()->create([
            'name' => 'Musterklinik',
        ]);

        System::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Aktives System',
            'system_type' => 'server',
            'status' => 'active',
        ]);

        System::query()->create([
            'organization_id' => $organization->id,
            'name' => 'Archiviertes System',
            'system_type' => 'server',
            'status' => 'retired',
            'archived_at' => now(),
        ]);

        $this->assertSame(1, System::query()->active()->count());
    }
}
