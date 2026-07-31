<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DicomConnection;
use App\Models\DicomNode;
use App\Models\Organization;
use App\Models\Role;
use App\Models\Site;
use App\Models\System;
use App\Models\User;
use App\Services\Audit\RegistryHistoryService;
use App\Support\RegistryAudit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RegistryHistoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_history_contains_system_nodes_connections_and_diagnostics_only(): void
    {
        $user = $this->viewer();
        $system = System::factory()->create();
        $node = DicomNode::factory()->create(['system_id' => $system->id]);
        $otherNode = DicomNode::factory()->create();
        $connection = DicomConnection::factory()->create(['source_dicom_node_id' => $node->id, 'target_dicom_node_id' => $otherNode->id]);
        $audit = new RegistryAudit;
        $audit->record('registry.system.updated', $system, $user);
        $audit->record('registry.dicom_node.updated', $node, $user);
        $audit->record('registry.dicom_connection.updated', $connection, $user);
        $audit->record('diagnostics.network.completed', $node, $user);
        $audit->record('registry.dicom_node.updated', DicomNode::factory()->create(), $user);

        $events = (new RegistryHistoryService)->forContext($user, $system)->pluck('event_type')->all();
        self::assertEqualsCanonicalizing(['registry.system.updated', 'registry.dicom_node.updated', 'registry.dicom_connection.updated', 'diagnostics.network.completed'], $events);
    }

    public function test_organization_history_inherits_site_department_and_system_context(): void
    {
        $user = $this->viewer();
        $organization = $this->organization('Organisation A');
        $site = $this->site($organization, 'Standort A');
        $department = $this->department($site, 'Abteilung A');
        $system = System::factory()->create(['organization_id' => $organization->id, 'site_id' => $site->id, 'department_id' => $department->id]);
        $audit = new RegistryAudit;
        foreach ([$organization, $site, $department, $system] as $subject) {
            $audit->record('registry.context.updated', $subject, $user);
        }
        $foreign = $this->organization('Organisation B');
        $audit->record('registry.organization.updated', $foreign, $user);

        self::assertCount(4, (new RegistryHistoryService)->forContext($user, $organization)->get());
        self::assertCount(1, (new RegistryHistoryService)->forContext($user, $organization, false)->get());
    }

    public function test_site_and_department_contexts_do_not_mix_foreign_branches(): void
    {
        $user = $this->viewer();
        $organization = $this->organization('Organisation A');
        $site = $this->site($organization, 'Standort A');
        $department = $this->department($site, 'Abteilung A');
        $system = System::factory()->create(['organization_id' => $site->organization_id, 'site_id' => $site->id, 'department_id' => $department->id]);
        $foreignDepartment = $this->department($this->site($this->organization('Organisation B'), 'Standort B'), 'Abteilung B');
        $audit = new RegistryAudit;
        $audit->record('registry.department.updated', $department, $user);
        $audit->record('registry.system.updated', $system, $user);
        $audit->record('registry.department.updated', $foreignDepartment, $user);
        $service = new RegistryHistoryService;

        self::assertCount(2, $service->forContext($user, $site)->get());
        self::assertCount(2, $service->forContext($user, $department)->get());
    }

    public function test_context_query_enforces_existing_view_policy(): void
    {
        $this->expectException(AuthorizationException::class);
        (new RegistryHistoryService)->forContext(User::factory()->create(), System::factory()->create())->get();
    }

    private function viewer(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());

        return $user;
    }

    private function organization(string $name): Organization
    {
        return Organization::query()->create(['name' => $name]);
    }

    private function site(Organization $organization, string $name): Site
    {
        return Site::query()->create(['organization_id' => $organization->id, 'name' => $name, 'country_code' => 'DE', 'timezone' => 'Europe/Berlin']);
    }

    private function department(Site $site, string $name): Department
    {
        return Department::query()->create(['site_id' => $site->id, 'name' => $name]);
    }
}
