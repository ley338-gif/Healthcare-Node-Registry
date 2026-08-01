<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\System;
use App\Models\User;
use App\Support\RegistryAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class AuditWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_open_paginated_audit_workspace(): void
    {
        $user = $this->auditor();
        $system = System::factory()->create();
        (new RegistryAudit)->record('registry.system.updated', $system, $user, ['changed_fields' => ['name']]);

        $this->actingAs($user)->get('/audit')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Audit/Index')->where('events.per_page', 50)->where('events.total', 1)
            ->where('events.data.0.entity.label', 'System'));
    }

    public function test_audit_workspace_rejects_user_without_permission(): void
    {
        $this->actingAs(User::factory()->create())->get('/audit')->assertForbidden();
    }

    public function test_filters_and_csv_export_use_existing_security_events(): void
    {
        $user = $this->auditor();
        $system = System::factory()->create();
        (new RegistryAudit)->record('diagnostics.network.completed', $system, $user, ['status' => 'failed']);
        (new RegistryAudit)->record('registry.system.updated', $system, $user, ['status' => 'success']);

        $this->actingAs($user)->get('/audit?only_errors=1')->assertInertia(fn (Assert $page) => $page->where('events.total', 1));
        $this->actingAs($user)->get('/audit/export/csv?only_tests=1')->assertOk()
            ->assertDownload()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_event_groups_and_structured_changes_are_projected_and_filterable(): void
    {
        $user = $this->auditor();
        $system = System::factory()->create();
        (new RegistryAudit)->record('registry.system.updated', $system, $user, [
            'changed_fields' => ['status'],
            'before' => ['status' => 'active'],
            'after' => ['status' => 'archived'],
        ]);
        (new RegistryAudit)->record('diagnostics.network.completed', $system, $user);

        $this->actingAs($user)->get('/audit?event_group=registry')->assertInertia(fn (Assert $page) => $page
            ->where('events.total', 1)
            ->where('events.data.0.event_group.value', 'registry')
            ->where('events.data.0.changes.0.label', 'Status')
            ->where('events.data.0.changes.0.before', 'active')
            ->where('events.data.0.changes.0.after', 'archived'));
    }

    public function test_archived_objects_do_not_offer_navigation(): void
    {
        $user = $this->auditor();
        $system = System::factory()->create(['archived_at' => now()]);
        (new RegistryAudit)->record('registry.system.archived', $system, $user);

        $this->actingAs($user)->get('/audit')->assertInertia(fn (Assert $page) => $page
            ->where('events.data.0.entity.navigable', false)
            ->where('events.data.0.entity.url', null));
    }

    private function auditor(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());

        return $user;
    }
}
