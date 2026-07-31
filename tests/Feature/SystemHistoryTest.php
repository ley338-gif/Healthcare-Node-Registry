<?php

namespace Tests\Feature;

use App\Models\DicomNode;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use App\Support\RegistryAudit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SystemHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_system_history_displays_descendant_events_and_details(): void
    {
        $this->withoutVite();
        $this->seed();
        $user = $this->administrator();
        $system = System::factory()->create();
        $node = DicomNode::factory()->create(['system_id' => $system->id]);

        (new RegistryAudit)->record('diagnostic.echo.completed', $node, $user, [
            'status' => 'success',
            'duration_ms' => 42,
            'before' => ['host' => 'old.example'],
            'after' => ['host' => 'new.example'],
        ]);

        $this->actingAs($user)->get("/systems/{$system->public_id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('history.data', 1)
                ->where('history.data.0.event_type', 'diagnostic.echo.completed')
                ->where('history.data.0.metadata.duration_ms', 42)
                ->where('historyStats.total', 1)
                ->has('historyEventTypes', 1)
                ->has('historyUsers', 1));
    }

    public function test_system_history_can_be_filtered_and_is_paginated(): void
    {
        $this->withoutVite();
        $this->seed();
        $user = $this->administrator();
        $system = System::factory()->create();
        $audit = new RegistryAudit;

        foreach (range(1, 17) as $number) {
            $audit->record($number === 17 ? 'registry.system.archived' : 'registry.system.updated', $system, $user, [
                'sequence' => $number,
            ]);
        }

        $this->actingAs($user)->get("/systems/{$system->public_id}?history_type=registry.system.updated")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('history.data', 15)
                ->where('history.total', 16)
                ->where('historyFilters.history_type', 'registry.system.updated'));

        $this->actingAs($user)->get("/systems/{$system->public_id}?history_page=2&history_type=registry.system.updated")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('history.data', 1));
    }

    public function test_unprivileged_user_cannot_view_system_history(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/systems/'.System::factory()->create()->public_id)
            ->assertForbidden();
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());

        return $user;
    }
}
