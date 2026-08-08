<?php

namespace Tests\Feature;

use App\Models\DicomNode;
use App\Models\Role;
use App\Models\User;
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
}
