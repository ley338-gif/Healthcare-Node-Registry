<?php

namespace Tests\Feature;

use App\Models\DicomNode;
use App\Models\Role;
use App\Models\User;
use App\Services\Diagnostics\DiagnosticTestResult;
use App\Services\Diagnostics\DiagnosticTestStatus;
use App\Services\Diagnostics\NetworkConnectionTest;
use App\Services\Diagnostics\NetworkProbe;
use App\Services\Diagnostics\NetworkProbeResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class NetworkDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_tcp_test_returns_steps_and_is_audited(): void
    {
        $user = $this->createRegistryManager();
        $node = DicomNode::factory()->create([
            'host' => 'pacs.example.test',
            'port' => 104,
        ]);
        $this->useProbe(['192.0.2.10'], new NetworkProbeResult(true));

        $response = $this->actingAs($user)->post("/tests/network/{$node->public_id}");

        $response
            ->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionHas('diagnosticResult.status', 'success')
            ->assertSessionHas('diagnosticResult.steps.0.name', 'dns_resolution')
            ->assertSessionHas('diagnosticResult.steps.1.name', 'tcp_connection');

        $this->assertDatabaseHas('security_events', [
            'event_type' => 'diagnostics.network.completed',
            'subject_type' => DicomNode::class,
            'subject_public_id' => $node->public_id,
        ]);
    }

    public function test_dns_failure_is_classified(): void
    {
        $node = DicomNode::factory()->create(['host' => 'missing.example.test']);
        $result = (new NetworkConnectionTest(new FakeNetworkProbe([])))->run($node);

        self::assertSame(DiagnosticTestStatus::Failed, $result->status);
        self::assertSame('dns_resolution', $result->steps[0]->name);
        self::assertSame('DNS-Auflösung fehlgeschlagen.', $result->summary);
    }

    public function test_timeout_is_classified(): void
    {
        $result = $this->runWithConnectionError('Connection timed out', 110);

        self::assertSame(DiagnosticTestStatus::Timeout, $result->status);
        self::assertSame('tcp_connection', $result->steps[1]->name);
    }

    public function test_connection_refused_is_classified(): void
    {
        $result = $this->runWithConnectionError('Connection refused', 111);

        self::assertSame(DiagnosticTestStatus::Failed, $result->status);
        self::assertSame('TCP-Verbindung wurde vom Ziel abgelehnt.', $result->summary);
    }

    public function test_invalid_registered_target_is_not_connected(): void
    {
        $node = DicomNode::factory()->create(['host' => 'invalid host']);
        $probe = new FakeNetworkProbe(['192.0.2.10'], new NetworkProbeResult(true));

        $result = (new NetworkConnectionTest($probe))->run($node);

        self::assertSame(DiagnosticTestStatus::Failed, $result->status);
        self::assertSame(0, $probe->connectionAttempts);
        self::assertSame('target_validation', $result->steps[0]->name);
    }

    public function test_unprivileged_user_cannot_run_network_test(): void
    {
        $node = DicomNode::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post("/tests/network/{$node->public_id}")
            ->assertForbidden();
    }

    public function test_archived_node_cannot_be_tested(): void
    {
        $user = $this->createRegistryManager();
        $node = DicomNode::factory()->create(['archived_at' => now()]);
        $probe = new FakeNetworkProbe(['192.0.2.10'], new NetworkProbeResult(true));
        $this->app->instance(NetworkConnectionTest::class, new NetworkConnectionTest($probe));

        $this->actingAs($user)
            ->post("/tests/network/{$node->public_id}")
            ->assertRedirect()
            ->assertSessionHas('error');

        self::assertSame(0, $probe->connectionAttempts);
    }

    public function test_unregistered_target_cannot_be_tested(): void
    {
        $user = $this->createRegistryManager();

        $this->actingAs($user)
            ->post('/tests/network/0198f08e-8b10-7000-8000-000000000000')
            ->assertNotFound();
    }

    private function runWithConnectionError(string $message, int $code): DiagnosticTestResult
    {
        $node = DicomNode::factory()->create(['host' => 'pacs.example.test']);
        $probe = new FakeNetworkProbe(
            ['192.0.2.10'],
            new NetworkProbeResult(false, $code, $message),
        );

        return (new NetworkConnectionTest($probe))->run($node);
    }

    /** @param list<string> $addresses */
    private function useProbe(array $addresses, NetworkProbeResult $connection): void
    {
        $this->app->instance(
            NetworkConnectionTest::class,
            new NetworkConnectionTest(new FakeNetworkProbe($addresses, $connection)),
        );
    }

    private function createRegistryManager(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $role = Role::query()->where('name', 'system-administrator')->firstOrFail();
        $user->roles()->attach($role);

        return $user;
    }
}

final class FakeNetworkProbe implements NetworkProbe
{
    public int $connectionAttempts = 0;

    /** @param list<string> $addresses */
    public function __construct(
        private readonly array $addresses,
        private readonly NetworkProbeResult $connection = new NetworkProbeResult(false),
    ) {}

    public function resolve(string $host): array
    {
        return $this->addresses;
    }

    public function connect(string $host, int $port, float $timeoutSeconds): NetworkProbeResult
    {
        $this->connectionAttempts++;

        return $this->connection;
    }
}
