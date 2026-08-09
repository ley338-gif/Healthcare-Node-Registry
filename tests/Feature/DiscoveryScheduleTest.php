<?php

namespace Tests\Feature;

use App\Jobs\RunDiscoveryScanJob;
use App\Models\DiscoveryAllowedNetwork;
use App\Models\DiscoveryRun;
use App\Models\DiscoverySchedule;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class DiscoveryScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_runner_can_store_a_daily_schedule_without_starting_a_run_immediately(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2026-08-10 08:00:00 UTC');
        $user = $this->runner();

        $response = $this->actingAs($user)->post('/discovery/runs', $this->payload());

        $response->assertSessionHasNoErrors()->assertRedirect('/discovery');
        $schedule = DiscoverySchedule::query()->firstOrFail();
        self::assertSame('daily', $schedule->frequency);
        self::assertSame('Europe/Berlin', $schedule->timezone);
        self::assertSame('2026-08-11T00:00:00+00:00', $schedule->next_run_at->toIso8601String());
        self::assertSame('192.168.20.0/29', $schedule->configuration['ip_range']);
        $this->assertDatabaseCount('discovery_runs', 0);
        $this->assertDatabaseHas('security_events', ['event_type' => 'discovery.schedule.created']);
        Queue::assertNothingPushed();
    }

    public function test_weekly_schedule_keeps_its_local_time_across_the_daylight_saving_boundary(): void
    {
        CarbonImmutable::setTestNow('2026-03-28 10:00:00 UTC');
        $user = $this->runner();
        $payload = $this->payload();
        $payload['schedule'] = [
            'frequency' => 'weekly',
            'scheduled_time' => '02:00',
            'day_of_week' => 1,
            'timezone' => 'Europe/Berlin',
        ];

        $this->actingAs($user)->post('/discovery/runs', $payload)->assertSessionHasNoErrors();

        $schedule = DiscoverySchedule::query()->firstOrFail();
        self::assertSame('2026-03-30T00:00:00+00:00', $schedule->next_run_at->toIso8601String());
        self::assertSame('02:00', $schedule->next_run_at->setTimezone('Europe/Berlin')->format('H:i'));
    }

    public function test_due_schedule_dispatches_a_discovery_run_and_advances_its_next_occurrence(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2026-08-10 00:05:00 UTC');
        $user = $this->runner();
        $schedule = DiscoverySchedule::factory()->create([
            'created_by' => $user->id,
            'next_run_at' => now()->subMinute(),
        ]);

        $this->artisan('discovery:run-schedules')->assertSuccessful()->expectsOutput('1 Discovery-Zeitplan/Zeitpläne gestartet.');

        $schedule->refresh();
        $run = DiscoveryRun::query()->firstOrFail();
        self::assertSame($schedule->id, $run->discovery_schedule_id);
        self::assertSame($run->id, $schedule->last_run_id);
        self::assertTrue($schedule->next_run_at->isFuture());
        self::assertNull($schedule->last_error);
        Queue::assertPushedOn('discovery', RunDiscoveryScanJob::class);
        $this->assertDatabaseHas('security_events', ['event_type' => 'discovery.schedule.dispatched']);
    }

    public function test_due_schedule_is_postponed_while_an_overlapping_run_is_active(): void
    {
        Queue::fake();
        CarbonImmutable::setTestNow('2026-08-10 00:05:00 UTC');
        $this->runner();
        $schedule = DiscoverySchedule::factory()->create(['next_run_at' => now()->subMinute()]);
        DiscoveryRun::factory()->create([
            'name' => 'Aktiver Scan',
            'ip_range' => '192.168.20.4/30',
            'status' => DiscoveryRun::STATUS_RUNNING,
        ]);

        $this->artisan('discovery:run-schedules')->assertSuccessful()->expectsOutput('0 Discovery-Zeitplan/Zeitpläne gestartet.');

        $schedule->refresh();
        self::assertSame('2026-08-10T00:10:00+00:00', $schedule->next_run_at->toIso8601String());
        self::assertStringContainsString('Aktiver Scan', (string) $schedule->last_error);
        Queue::assertNothingPushed();
    }

    public function test_schedule_is_disabled_when_its_network_is_no_longer_allowed(): void
    {
        Queue::fake();
        $this->runner();
        $schedule = DiscoverySchedule::factory()->create(['next_run_at' => now()->subMinute()]);
        DiscoveryAllowedNetwork::query()->delete();
        DiscoveryAllowedNetwork::query()->create([
            'cidr' => '10.50.0.0/24',
            'description' => 'Andere Freigabe',
            'active' => true,
        ]);

        $this->artisan('discovery:run-schedules')->assertSuccessful();

        $schedule->refresh();
        self::assertFalse($schedule->active);
        self::assertNotNull($schedule->last_error);
        $this->assertDatabaseCount('discovery_runs', 0);
        $this->assertDatabaseHas('security_events', ['event_type' => 'discovery.schedule.disabled']);
    }

    public function test_runner_can_pause_and_delete_a_schedule_but_unprivileged_user_cannot(): void
    {
        $runner = $this->runner();
        $schedule = DiscoverySchedule::factory()->create(['created_by' => $runner->id]);

        $this->actingAs(User::factory()->create())
            ->patch("/discovery/schedules/{$schedule->public_id}", ['active' => false])
            ->assertForbidden();
        $this->actingAs($runner)
            ->patch("/discovery/schedules/{$schedule->public_id}", ['active' => false])
            ->assertRedirect();
        $this->assertDatabaseHas('discovery_schedules', ['id' => $schedule->id, 'active' => false]);

        $this->actingAs($runner)->delete("/discovery/schedules/{$schedule->public_id}")->assertRedirect();
        self::assertSoftDeleted($schedule);
        $this->assertDatabaseHas('security_events', ['event_type' => 'discovery.schedule.deleted']);
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'name' => 'Nächtlicher Radiologie-Scan',
            'location' => 'Hauptklinik',
            'department' => 'Radiologie',
            'ip_range' => '192.168.20.0/29',
            'exclude_ips' => [],
            'description' => null,
            'execution_mode' => 'scheduled',
            'schedule' => [
                'frequency' => 'daily',
                'scheduled_time' => '02:00',
                'day_of_week' => 1,
                'timezone' => 'Europe/Berlin',
            ],
            'scan_options' => [
                'ping_enabled' => true,
                'reverse_dns_enabled' => true,
                'tcp_scan_enabled' => true,
                'dicom_check_enabled' => true,
                'scan_unresponsive_hosts' => false,
                'max_parallel_hosts' => 4,
                'timeout_seconds' => 2,
                'retries' => 1,
                'profile' => 'standard',
            ],
            'ports' => [
                ['port' => 11112, 'protocol' => 'tcp', 'label' => 'DICOM', 'is_dicom_candidate' => true, 'enabled' => true],
            ],
            'ae_candidates' => [
                ['ae_title' => 'HNR_DISCOVERY', 'role' => 'calling', 'source' => 'default'],
            ],
            'confirmed' => true,
        ];
    }

    private function runner(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $role = Role::query()->create(['name' => 'discovery-runner-'.uniqid(), 'display_name' => 'Discovery Runner']);
        $role->permissions()->sync(Permission::query()->where('name', 'discovery.run')->pluck('id'));
        $user->roles()->attach($role);

        return $user;
    }
}
