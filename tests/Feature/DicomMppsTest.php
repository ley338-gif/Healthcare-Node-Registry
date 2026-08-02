<?php

namespace Tests\Feature;

use App\Models\DicomNode;
use App\Models\Role;
use App\Models\User;
use App\Services\Diagnostics\DiagnosticTestStatus;
use App\Services\Diagnostics\DicomMppsTest as MppsService;
use App\Services\Diagnostics\MppsCommandResult;
use App\Services\Diagnostics\MppsCommandRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DicomMppsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        fake()->unique(true);
    }

    public function test_success_reports_both_dimse_statuses(): void
    {
        $node = DicomNode::factory()->create(['supports_mpps' => true]);
        $result = (new MppsService(new FakeMppsRunner(new MppsCommandResult(true, 0, 0, '1.2.3', 'MPPS completed'))))->run($node, 'NODE_REGISTRY', $node->ae_title);

        self::assertSame(DiagnosticTestStatus::Success, $result->status);
        self::assertSame('dicom_mpps', $result->testType);
        self::assertSame('0x0000', $result->details['nCreateStatus']);
        self::assertSame('0x0000', $result->details['nSetStatus']);
    }

    public function test_confirmation_is_required(): void
    {
        $node = DicomNode::factory()->create(['supports_mpps' => true]);

        $this->actingAs($this->administrator())->post("/tests/mpps/{$node->public_id}", $this->input(false))->assertSessionHasErrors('confirmed');
        $this->assertDatabaseCount('diagnostic_test_runs', 0);
    }

    public function test_unsupported_node_is_rejected(): void
    {
        $node = DicomNode::factory()->create(['supports_mpps' => false]);

        $this->actingAs($this->administrator())->post("/tests/mpps/{$node->public_id}", $this->input(true))->assertSessionHas('error');
        $this->assertDatabaseCount('diagnostic_test_runs', 0);
    }

    public function test_confirmed_run_is_persisted_and_audited(): void
    {
        $node = DicomNode::factory()->create(['supports_mpps' => true]);
        $this->app->instance(MppsService::class, new MppsService(new FakeMppsRunner(new MppsCommandResult(true, 0, 0, '1.2.826.0.1.3680043.10.987.1', 'MPPS completed'))));

        $this->actingAs($this->administrator())->post("/tests/mpps/{$node->public_id}", $this->input(true))->assertSessionHas('success');

        $this->assertDatabaseHas('diagnostic_test_runs', ['dicom_node_id' => $node->id, 'test_type' => 'dicom_mpps']);
        $this->assertDatabaseHas('security_events', ['event_type' => 'diagnostics.mpps.completed', 'subject_public_id' => $node->public_id]);
    }

    /** @return array<string, mixed> */
    private function input(bool $confirmed): array
    {
        return ['confirmed' => $confirmed, 'calling_ae_title' => 'NODE_REGISTRY', 'called_ae_title' => 'MPPS_SCP'];
    }

    private function administrator(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());

        return $user;
    }
}

final readonly class FakeMppsRunner implements MppsCommandRunner
{
    public function __construct(private MppsCommandResult $result) {}

    public function run(DicomNode $node, string $callingAeTitle, string $calledAeTitle): MppsCommandResult
    {
        return $this->result;
    }
}
