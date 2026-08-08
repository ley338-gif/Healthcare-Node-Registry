<?php

namespace Tests\Feature;

use App\Models\DicomNode;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\DiagnosticPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class TestWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_registry_manager_can_view_test_workspace(): void
    {
        $this->seed();

        $user = User::factory()->create();

        $role = Role::query()
            ->where('name', 'system-administrator')
            ->firstOrFail();

        $user->roles()->attach($role);

        DicomNode::factory()->create(['modality' => 'DX']);

        $this
            ->actingAs($user)
            ->get('/tests')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tests/Index')
                ->has('nodes', 1)
                ->where('nodes.0.modality', 'DX')
                ->where('canRunEcho', true)
                ->where('defaultCallingAeTitle', 'NODE_REGISTRY'));
    }

    public function test_default_calling_ae_title_reflects_central_configuration(): void
    {
        config(['diagnostics.default_calling_ae_title' => 'HNR_TEST']);
        $this->seed();

        $user = User::factory()->create();

        $role = Role::query()
            ->where('name', 'system-administrator')
            ->firstOrFail();

        $user->roles()->attach($role);

        $this
            ->actingAs($user)
            ->get('/tests')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tests/Index')
                ->where('defaultCallingAeTitle', 'HNR_TEST'));
    }

    public function test_workspace_exposes_each_granular_diagnostic_permission_independently(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $role = Role::query()->create(['name' => 'echo-operator', 'display_name' => 'Echo Operator']);
        $role->permissions()->attach(Permission::query()->whereIn('name', [
            'registry.view',
            DiagnosticPermission::Echo->value,
            DiagnosticPermission::CapabilityMatrix->value,
        ])->pluck('id'));
        $user->roles()->attach($role);

        $this->actingAs($user)->get('/tests')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('canRunEcho', true)
            ->where('canRunNetwork', true)
            ->where('canRunWorklist', false)
            ->where('canRunMpps', false)
            ->where('canRunPacsQuery', false)
            ->where('canRunStorage', false)
            ->where('canRunStorageCommitment', false)
            ->where('canRunCapabilityMatrix', true));
    }

    public function test_default_diagnostic_roles_follow_least_privilege(): void
    {
        $this->seed();

        $pacsAdministrator = Role::query()->where('name', 'pacs-administrator')->firstOrFail();
        $readOnly = Role::query()->where('name', 'read-only')->firstOrFail();

        self::assertEqualsCanonicalizing(
            array_column(DiagnosticPermission::cases(), 'value'),
            $pacsAdministrator->permissions()->where('name', 'like', 'diagnostics.%')->pluck('name')->all(),
        );
        self::assertFalse($readOnly->permissions()->where('name', 'like', 'diagnostics.%')->exists());
        self::assertTrue($readOnly->permissions()->where('name', 'registry.view')->exists());
    }

    public function test_connection_only_move_and_get_services_are_not_accepted_as_workspace_prefill(): void
    {
        $this->seed();
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());

        $this->actingAs($user)->get('/tests?service=move')
            ->assertRedirect()
            ->assertSessionHasErrors('service');
        $this->actingAs($user)->get('/tests?service=get')
            ->assertRedirect()
            ->assertSessionHasErrors('service');
    }
}
