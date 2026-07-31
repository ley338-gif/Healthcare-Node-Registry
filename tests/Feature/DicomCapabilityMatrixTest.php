<?php

namespace Tests\Feature;

use App\Models\DicomNode;
use App\Models\Role;
use App\Models\User;
use App\Services\Diagnostics\CapabilityMatrixResult;
use App\Services\Diagnostics\CapabilityMatrixRunner;
use App\Services\Diagnostics\DiagnosticTestStatus;
use App\Services\Diagnostics\DicomCapabilityMatrixTest as CapabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DicomCapabilityMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_matrix_normalizes_accepted_rejected_unsupported_and_not_tested_contexts(): void
    {
        $node = DicomNode::factory()->create(['supports_store' => true]);
        $result = (new CapabilityService(new FakeCapabilityRunner(new CapabilityMatrixResult(true, [1 => 0, 3 => 1, 5 => 3]))))->run($node, 'NODE_REGISTRY', $node->ae_title);

        self::assertSame(DiagnosticTestStatus::Success, $result->status);
        self::assertSame('presentation_context', $result->details['verificationMode']);
        self::assertSame('accepted', $result->details['matrix'][0]['status']);
        self::assertSame('rejected', $result->details['matrix'][1]['status']);
        self::assertSame('unsupported', $result->details['matrix'][2]['status']);
        self::assertSame('not_tested', $result->details['matrix'][3]['status']);
        self::assertCount(49, $result->details['matrix']);
    }

    public function test_run_is_permission_checked_persisted_and_audited(): void
    {
        $node = DicomNode::factory()->create(['supports_store' => true]);
        $this->app->instance(CapabilityService::class, new CapabilityService(new FakeCapabilityRunner(new CapabilityMatrixResult(true, [1 => 0]))));
        $admin = $this->administrator();

        $this->actingAs($admin)->post("/tests/capabilities/{$node->public_id}", ['calling_ae_title' => 'NODE_REGISTRY', 'called_ae_title' => $node->ae_title])->assertSessionHas('success');
        $this->assertDatabaseHas('diagnostic_test_runs', ['dicom_node_id' => $node->id, 'test_type' => 'dicom_capability_matrix']);
        $this->assertDatabaseHas('security_events', ['event_type' => 'diagnostics.capability-matrix.completed']);
    }

    public function test_unprivileged_user_cannot_run_matrix(): void
    {
        $node = DicomNode::factory()->create(['supports_store' => true]);
        $this->actingAs(User::factory()->create())->post("/tests/capabilities/{$node->public_id}", ['calling_ae_title' => 'NODE_REGISTRY', 'called_ae_title' => $node->ae_title])->assertForbidden();
    }

    private function administrator(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());

        return $user;
    }
}

final readonly class FakeCapabilityRunner implements CapabilityMatrixRunner
{
    public function __construct(private CapabilityMatrixResult $result) {}

    public function run(DicomNode $node, string $callingAeTitle, string $calledAeTitle, array $contexts): CapabilityMatrixResult
    {
        return $this->result;
    }
}
