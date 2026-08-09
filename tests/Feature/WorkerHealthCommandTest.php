<?php

namespace Tests\Feature;

use App\Services\WorkerHeartbeat;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

final class WorkerHealthCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $heartbeatPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->heartbeatPath = storage_path('framework/testing-worker-heartbeat');
        config()->set('worker.heartbeat_path', $this->heartbeatPath);
        config()->set('worker.heartbeat_max_age_seconds', 15);
        @unlink($this->heartbeatPath);
    }

    protected function tearDown(): void
    {
        @unlink($this->heartbeatPath);

        parent::tearDown();
    }

    public function test_queue_loop_records_a_healthy_heartbeat(): void
    {
        Event::dispatch(new Looping('database', 'discovery'));

        self::assertFileExists($this->heartbeatPath);
        $this->artisan('registry:worker-health')
            ->expectsOutputToContain('Worker healthy')
            ->assertSuccessful();
    }

    public function test_missing_heartbeat_is_unhealthy(): void
    {
        $this->artisan('registry:worker-health')
            ->expectsOutputToContain('kein gültiger Heartbeat')
            ->assertFailed();
    }

    public function test_stale_heartbeat_is_unhealthy(): void
    {
        file_put_contents($this->heartbeatPath, json_encode([
            'recorded_at' => time() - 60,
            'busy_until' => null,
        ], JSON_THROW_ON_ERROR));

        $this->artisan('registry:worker-health')
            ->expectsOutputToContain('60 Sekunden alt')
            ->assertFailed();
    }

    public function test_processing_job_remains_healthy_until_its_timeout(): void
    {
        $job = $this->createStub(Job::class);
        $job->method('payload')->willReturn(['timeout' => 120]);

        Event::dispatch(new JobProcessing('database', $job));

        self::assertTrue(app(WorkerHeartbeat::class)->isBusy());
        $this->artisan('registry:worker-health')
            ->expectsOutputToContain('beschäftigt')
            ->assertSuccessful();
    }

    public function test_expired_busy_reservation_is_unhealthy(): void
    {
        file_put_contents($this->heartbeatPath, json_encode([
            'recorded_at' => time() - 60,
            'busy_until' => time() - 1,
        ], JSON_THROW_ON_ERROR));

        $this->artisan('registry:worker-health')
            ->expectsOutputToContain('60 Sekunden alt')
            ->assertFailed();
    }

    public function test_service_rejects_invalid_heartbeat_content(): void
    {
        file_put_contents($this->heartbeatPath, 'invalid');

        self::assertNull(app(WorkerHeartbeat::class)->ageSeconds());
        self::assertFalse(app(WorkerHeartbeat::class)->isFresh());
    }
}
