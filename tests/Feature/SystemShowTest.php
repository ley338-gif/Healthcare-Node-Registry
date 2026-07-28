<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SystemShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_view_a_system(): void
    {
        $this->withoutVite();
        $this->seed();

        $user = User::factory()->create();

        $role = Role::query()
            ->where('name', 'system-administrator')
            ->firstOrFail();

        $user->roles()->attach($role);

        $system = System::factory()->create([
            'name' => 'PACS Produktion',
            'system_type' => 'pacs',
            'status' => 'active',
        ]);

        $this
            ->actingAs($user)
            ->get("/systems/{$system->public_id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Registry/Systems/Show')
                ->where('system.name', 'PACS Produktion')
                ->where('system.system_type', 'pacs')
                ->where('system.status', 'active')
                ->has('system.organization')
                ->has('system.site')
                ->has('system.department')
                ->has('dicomNodes')
                ->has('dicomConnections')
                ->has('dicomNodeOptions')
                ->where('canManageDicomNodes', true)
                ->has('systemTypes')
                ->has('statuses')
                ->where('canManage', true)
            );
    }

    public function test_unprivileged_user_cannot_view_a_system(): void
    {
        $user = User::factory()->create();
        $system = System::factory()->create();

        $this
            ->actingAs($user)
            ->get("/systems/{$system->public_id}")
            ->assertForbidden();
    }
}
