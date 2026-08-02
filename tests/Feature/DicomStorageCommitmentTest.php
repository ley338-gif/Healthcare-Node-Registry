<?php

namespace Tests\Feature;

use App\Models\DicomNode;
use App\Models\Role;
use App\Models\User;
use App\Services\Diagnostics\DiagnosticTestStatus;
use App\Services\Diagnostics\DicomStorageCommitmentTest as CommitmentService;
use App\Services\Diagnostics\StorageCommandResult;
use App\Services\Diagnostics\StorageCommandRunner;
use App\Services\Diagnostics\StorageCommitmentCommandResult;
use App\Services\Diagnostics\StorageCommitmentCommandRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DicomStorageCommitmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        fake()->unique(true);
    }

    public function test_success_requires_store_action_and_event_report(): void
    {
        $node = DicomNode::factory()->create(['supports_storage_commitment' => true]);
        $result = $this->service()->run($node, 'NODE_REGISTRY', $node->ae_title);

        self::assertSame(DiagnosticTestStatus::Success, $result->status);
        self::assertSame('dicom_storage_commitment', $result->testType);
        self::assertSame('0x0000', $result->details['nActionStatus']);
        self::assertSame(1, $result->details['eventType']);
    }

    public function test_event_report_timeout_is_classified(): void
    {
        $node = DicomNode::factory()->create(['supports_storage_commitment' => true]);
        $service = new CommitmentService(
            new FakeCommitmentStorageRunner(new StorageCommandResult(true, 0, '', '1.2.3')),
            new FakeStorageCommitmentRunner(new StorageCommitmentCommandResult(false, 0, null, '1.2.4', null, 'timeout', 'event_report_timeout')),
        );

        self::assertSame(DiagnosticTestStatus::Timeout, $service->run($node, 'NODE_REGISTRY', $node->ae_title)->status);
    }

    public function test_confirmation_is_required_and_unsupported_nodes_are_rejected(): void
    {
        $node = DicomNode::factory()->create(['supports_storage_commitment' => true]);
        $this->actingAs($this->administrator())->post("/tests/storage-commitment/{$node->public_id}", $this->input(false))->assertSessionHasErrors('confirmed');

        $unsupported = DicomNode::factory()->create(['supports_storage_commitment' => false]);
        $this->actingAs($this->administrator())->post("/tests/storage-commitment/{$unsupported->public_id}", $this->input(true))->assertSessionHas('error');
        $this->assertDatabaseCount('diagnostic_test_runs', 0);
    }

    public function test_confirmed_run_is_persisted_and_audited(): void
    {
        $node = DicomNode::factory()->create(['supports_storage_commitment' => true]);
        $this->app->instance(CommitmentService::class, $this->service());

        $this->actingAs($this->administrator())->post("/tests/storage-commitment/{$node->public_id}", $this->input(true))->assertSessionHas('success');

        $this->assertDatabaseHas('diagnostic_test_runs', ['dicom_node_id' => $node->id, 'test_type' => 'dicom_storage_commitment']);
        $this->assertDatabaseHas('security_events', ['event_type' => 'diagnostics.storage_commitment.completed', 'subject_public_id' => $node->public_id]);
    }

    private function service(): CommitmentService
    {
        return new CommitmentService(
            new FakeCommitmentStorageRunner(new StorageCommandResult(true, 0, '', '1.2.826.0.1.3680043.10.987.3.1')),
            new FakeStorageCommitmentRunner(new StorageCommitmentCommandResult(true, 0, 1, '1.2.826.0.1.3680043.10.987.4.1', null, 'confirmed')),
        );
    }

    /** @return array<string, mixed> */
    private function input(bool $confirmed): array
    {
        return ['confirmed' => $confirmed, 'calling_ae_title' => 'NODE_REGISTRY', 'called_ae_title' => 'COMMIT_SCP'];
    }

    private function administrator(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());

        return $user;
    }
}

final readonly class FakeCommitmentStorageRunner implements StorageCommandRunner
{
    public function __construct(private StorageCommandResult $result) {}

    public function run(DicomNode $node, string $callingAeTitle, string $calledAeTitle): StorageCommandResult
    {
        return $this->result;
    }
}

final readonly class FakeStorageCommitmentRunner implements StorageCommitmentCommandRunner
{
    public function __construct(private StorageCommitmentCommandResult $result) {}

    public function run(DicomNode $node, string $callingAeTitle, string $calledAeTitle, string $sopClassUid, string $sopInstanceUid): StorageCommitmentCommandResult
    {
        return $this->result;
    }
}
