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

        DicomNode::factory()->create();

        $this
            ->actingAs($user)
            ->get('/tests')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Tests/Index')
                ->has('nodes', 1)
                ->where('canRunEcho', true));
    }
}
