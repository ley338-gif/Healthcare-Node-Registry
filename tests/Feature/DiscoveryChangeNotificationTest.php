<?php

namespace Tests\Feature;

use App\Models\DiscoveredHost;
use App\Models\DiscoveryRun;
use App\Models\DiscoverySchedule;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Notifications\DiscoveryRunChangedNotification;
use App\Services\Discovery\DiscoveryChangeNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class DiscoveryChangeNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_scheduled_run_notifies_active_discovery_users_once(): void
    {
        $viewer = $this->discoveryUser('discovery.view');
        $runner = $this->discoveryUser('discovery.run');
        $manager = $this->discoveryUser('discovery.manage');
        $inactive = $this->discoveryUser('discovery.view', false);
        $unauthorized = User::factory()->create();
        [$schedule, $baseline, $current] = $this->changedScheduledRuns();

        $service = $this->app->make(DiscoveryChangeNotificationService::class);
        $first = $service->notifyForCompletedRun($current);
        $second = $service->notifyForCompletedRun($current);

        self::assertSame($baseline->id, $first['baseline_run_id']);
        self::assertSame(1, $first['new']);
        self::assertSame(1, $first['missing']);
        self::assertSame(3, $first['recipients']);
        self::assertSame(3, $first['sent']);
        self::assertSame(0, $first['skipped']);
        self::assertSame(0, $second['sent']);
        self::assertSame(3, $second['skipped']);

        foreach ([$viewer, $runner, $manager] as $recipient) {
            $notification = $recipient->fresh()->notifications->sole();
            self::assertSame(DiscoveryRunChangedNotification::class, $notification->type);
            self::assertSame($schedule->public_id, $notification->data['schedule_public_id']);
            self::assertSame($current->public_id, $notification->data['current_run_public_id']);
            self::assertSame($baseline->public_id, $notification->data['baseline_run_public_id']);
            self::assertSame(['10.20.30.2'], $notification->data['new_host_ips']);
            self::assertSame(['10.20.30.3'], $notification->data['missing_host_ips']);
        }
        self::assertCount(0, $inactive->fresh()->notifications);
        self::assertCount(0, $unauthorized->fresh()->notifications);
    }

    public function test_manual_incomplete_first_or_unchanged_runs_do_not_notify(): void
    {
        $recipient = $this->discoveryUser('discovery.view');
        $service = $this->app->make(DiscoveryChangeNotificationService::class);
        $schedule = DiscoverySchedule::factory()->create();

        $manual = $this->completedRun(null, now()->subDays(3));
        $firstScheduled = $this->completedRun($schedule, now()->subDays(2));
        $partial = DiscoveryRun::factory()->create([
            'discovery_schedule_id' => $schedule->id,
            'status' => DiscoveryRun::STATUS_PARTIALLY_FAILED,
            'ip_range' => '10.20.30.0/24',
            'finished_at' => now()->subDay(),
        ]);
        $unchanged = $this->completedRun($schedule, now());
        $this->host($firstScheduled, '10.20.30.1');
        $this->host($unchanged, '10.20.30.1');

        self::assertSame(0, $service->notifyForCompletedRun($manual)['sent']);
        self::assertSame(0, $service->notifyForCompletedRun($firstScheduled)['sent']);
        self::assertSame(0, $service->notifyForCompletedRun($partial)['sent']);
        self::assertSame(0, $service->notifyForCompletedRun($unchanged)['sent']);
        self::assertCount(0, $recipient->fresh()->notifications);
    }

    public function test_dashboard_lists_changes_and_opening_notification_marks_it_read(): void
    {
        $this->withoutVite();
        $recipient = $this->discoveryUser('discovery.view');
        [, $baseline, $current] = $this->changedScheduledRuns();
        $this->app->make(DiscoveryChangeNotificationService::class)->notifyForCompletedRun($current);
        $notification = $recipient->fresh()->unreadNotifications->sole();

        $this->actingAs($recipient)->get('/')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('discoveryChanges.unread', 1)
            ->has('discoveryChanges.items', 1)
            ->where('discoveryChanges.items.0.id', $notification->id)
            ->where('discoveryChanges.items.0.newHostsCount', 1)
            ->where('discoveryChanges.items.0.missingHostsCount', 1)
            ->where('discoveryChanges.items.0.unread', true));

        $this->actingAs($recipient)
            ->get(route('notifications.show', ['notification' => $notification->id]))
            ->assertRedirect(route('discovery.runs.compare', [
                'discoveryRun' => $current->public_id,
                'baselineRun' => $baseline->public_id,
            ]));

        self::assertNotNull($notification->fresh()->read_at);
    }

    public function test_discovery_notification_redirect_rechecks_current_permission(): void
    {
        $recipient = $this->discoveryUser('discovery.view');
        [$schedule, $baseline, $current] = $this->changedScheduledRuns();
        $recipient->notify(new DiscoveryRunChangedNotification($current, $baseline, $schedule, ['10.20.30.2'], []));
        $notification = $recipient->fresh()->notifications->sole();
        $recipient->roles()->detach();

        $this->actingAs($recipient)
            ->get(route('notifications.show', ['notification' => $notification->id]))
            ->assertForbidden();
        self::assertNull($notification->fresh()->read_at);
    }

    /** @return array{DiscoverySchedule, DiscoveryRun, DiscoveryRun} */
    private function changedScheduledRuns(): array
    {
        $schedule = DiscoverySchedule::factory()->create();
        $baseline = $this->completedRun($schedule, now()->subDay());
        $current = $this->completedRun($schedule, now());
        $this->host($baseline, '10.20.30.1');
        $this->host($current, '10.20.30.1');
        $this->host($current, '10.20.30.2');
        $this->host($baseline, '10.20.30.3');

        return [$schedule, $baseline, $current];
    }

    private function completedRun(?DiscoverySchedule $schedule, mixed $finishedAt): DiscoveryRun
    {
        return DiscoveryRun::factory()->create([
            'discovery_schedule_id' => $schedule?->id,
            'status' => DiscoveryRun::STATUS_COMPLETED,
            'ip_range' => '10.20.30.0/24',
            'finished_at' => $finishedAt,
        ]);
    }

    private function host(DiscoveryRun $run, string $ipAddress): DiscoveredHost
    {
        return DiscoveredHost::factory()->create([
            'discovery_run_id' => $run->id,
            'ip_address' => $ipAddress,
            'hostname' => 'host-'.str_replace('.', '-', $ipAddress),
            'reverse_dns' => null,
            'is_reachable' => true,
            'confidence_score' => DiscoveredHost::CONFIDENCE_UNKNOWN,
            'confidence_percentage' => 0,
            'suggested_system_type' => null,
        ]);
    }

    private function discoveryUser(string $permissionName, bool $active = true): User
    {
        $this->seed();
        $permission = Permission::query()->where('name', $permissionName)->firstOrFail();
        $role = Role::query()->create([
            'name' => 'notification-'.$permissionName.'-'.uniqid(),
            'display_name' => 'Discovery Notification Test',
        ]);
        $role->permissions()->attach($permission);
        $user = User::factory()->create(['is_active' => $active]);
        $user->roles()->attach($role);

        return $user;
    }
}
