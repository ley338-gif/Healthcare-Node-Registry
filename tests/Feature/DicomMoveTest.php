<?php

namespace Tests\Feature;

use App\Models\DicomConnection;
use App\Models\DicomNode;
use App\Models\Role;
use App\Models\User;
use App\Services\Diagnostics\DiagnosticTestResult;
use App\Services\Diagnostics\DiagnosticTestStatus;
use App\Services\Diagnostics\DicomMoveTest as MoveService;
use App\Services\Diagnostics\MoveCommandResult;
use App\Services\Diagnostics\MoveCommandRunner;
use App\Services\Diagnostics\NativeMoveCommandRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DicomMoveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        fake()->unique(true);
    }

    public function test_success_contains_move_destination_and_synthetic_study_uid(): void
    {
        $result = $this->execute(new MoveCommandResult(true, 0, 'Response status 0x0000', '1.2.826.0.1.3680043.10.987.999.1'));

        self::assertSame(DiagnosticTestStatus::Success, $result->status);
        self::assertSame('dicom_move', $result->testType);
        self::assertSame('1.2.826.0.1.3680043.10.987.999.1', $result->details['studyInstanceUid']);
        self::assertSame('0x0000', $result->details['dimseStatus']);
        self::assertNotEmpty($result->details['destinationAeTitle']);
    }

    public function test_failures_are_classified(): void
    {
        self::assertSame('association_rejected', $this->execute(new MoveCommandResult(false, 1, 'Association Rejected', '1.2.3'))->details['failureType']);
        fake()->unique(true);
        self::assertSame('unknown_move_destination', $this->execute(new MoveCommandResult(false, 1, 'Unknown Move Destination', '1.2.3'))->details['failureType']);
        fake()->unique(true);
        self::assertSame('timeout', $this->execute(new MoveCommandResult(false, 1, 'timeout', '1.2.3', true))->details['failureType']);
    }

    public function test_explicit_confirmation_is_required(): void
    {
        [$connection] = $this->connection();

        $this->actingAs($this->administrator())
            ->post("/tests/move/{$connection->public_id}", $this->input(false))
            ->assertSessionHasErrors('confirmed');

        $this->assertDatabaseCount('diagnostic_test_runs', 0);
        $this->assertDatabaseMissing('security_events', ['event_type' => 'diagnostics.move.completed']);
    }

    public function test_confirmed_move_run_is_persisted_and_confirmation_is_audited(): void
    {
        [$connection, $target, $destination] = $this->connection();
        $this->app->instance(MoveService::class, new MoveService(new FakeMoveRunner(new MoveCommandResult(true, 0, 'status 0x0000', '1.2.826.0.1.3680043.10.987.999.1'))));

        $this->actingAs($this->administrator())
            ->post("/tests/move/{$connection->public_id}", $this->input(true))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('diagnostic_test_runs', [
            'dicom_node_id' => $target->id,
            'test_type' => 'dicom_move',
        ]);
        $this->assertDatabaseHas('security_events', [
            'event_type' => 'diagnostics.move.completed',
            'subject_public_id' => $connection->public_id,
        ]);

        $event = $connection->fresh()->getConnection()->table('security_events')->where('event_type', 'diagnostics.move.completed')->first();
        $metadata = json_decode((string) $event->metadata, true, flags: JSON_THROW_ON_ERROR);
        self::assertTrue($metadata['authorized_test_confirmed']);
        self::assertSame($destination->public_id, $metadata['destination_node_public_id']);
    }

    public function test_only_move_connections_with_destination_can_run(): void
    {
        $connection = DicomConnection::factory()->create(['service' => DicomConnection::SERVICE_STORE]);

        $this->actingAs($this->administrator())
            ->post("/tests/move/{$connection->public_id}", $this->input(true))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('diagnostic_test_runs', 0);
    }

    public function test_native_runner_uses_only_configured_synthetic_uid(): void
    {
        config(['diagnostics.move_test_study_instance_uid' => '1.2.826.0.1.3680043.10.987.999.42']);
        $target = DicomNode::factory()->create(['host' => '127.0.0.1', 'port' => 1]);
        $destination = DicomNode::factory()->create();

        $result = (new NativeMoveCommandRunner)->run($target, $destination, 'NODE_REGISTRY', $target->ae_title);

        self::assertSame('1.2.826.0.1.3680043.10.987.999.42', $result->studyInstanceUid);
        self::assertFalse($result->successful);
    }

    private function execute(MoveCommandResult $command): DiagnosticTestResult
    {
        $target = DicomNode::factory()->create();
        $destination = DicomNode::factory()->create();

        return (new MoveService(new FakeMoveRunner($command)))->run($target, $destination, 'NODE_REGISTRY', $target->ae_title);
    }

    /** @return array{DicomConnection, DicomNode, DicomNode} */
    private function connection(): array
    {
        $source = DicomNode::factory()->create();
        $target = DicomNode::factory()->create();
        $destination = DicomNode::factory()->create();
        $connection = DicomConnection::factory()->create([
            'source_dicom_node_id' => $source->id,
            'target_dicom_node_id' => $target->id,
            'destination_dicom_node_id' => $destination->id,
            'service' => DicomConnection::SERVICE_MOVE,
            'test_enabled' => true,
        ]);

        return [$connection, $target, $destination];
    }

    /** @return array<string, mixed> */
    private function input(bool $confirmed): array
    {
        return [
            'confirmed' => $confirmed,
            'calling_ae_title' => 'NODE_REGISTRY',
            'called_ae_title' => 'MOVE_SCP',
            'patient_id' => 'REAL-PATIENT-MUST-BE-IGNORED',
            'study_instance_uid' => '1.2.3.4.5',
        ];
    }

    private function administrator(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());

        return $user;
    }
}

final readonly class FakeMoveRunner implements MoveCommandRunner
{
    public function __construct(private MoveCommandResult $result) {}

    public function run(DicomNode $targetNode, DicomNode $destinationNode, string $callingAeTitle, string $calledAeTitle): MoveCommandResult
    {
        return $this->result;
    }
}
