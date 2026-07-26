<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_dashboard_control_center(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->where('summary.systems', 0)
                ->where('summary.connections', 0)
                ->where('summary.online', 0)
                ->where('summary.offline', 0)
                ->where('summary.documents', 0)
                ->has('recentChanges')
            );
    }
}
