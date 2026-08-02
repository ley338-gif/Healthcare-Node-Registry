<?php

namespace Tests\Feature;

use App\Models\DicomConnection;
use App\Models\DicomNode;
use App\Models\Role;
use App\Models\User;
use App\Services\Diagnostics\DicomGetTest as GetService;
use App\Services\Diagnostics\GetCommandResult;
use App\Services\Diagnostics\GetCommandRunner;
use App\Services\Diagnostics\NativeGetCommandRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DicomGetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        fake()->unique(true);
    }

    public function test_success_contains_synthetic_uid_and_received_count(): void
    {
        $node = DicomNode::factory()->create();
        $result = (new GetService(new FakeGetRunner(new GetCommandResult(true, 0, 'status 0x0000', '1.2.826.0.1.3680043.10.987.999.2', 2))))->run($node, 'NODE_REGISTRY', $node->ae_title);

        self::assertSame('dicom_get', $result->testType);
        self::assertSame('1.2.826.0.1.3680043.10.987.999.2', $result->details['studyInstanceUid']);
        self::assertSame(2, $result->details['receivedObjectCount']);
        self::assertSame('0x0000', $result->details['dimseStatus']);
    }

    public function test_confirmation_is_required_and_confirmed_run_is_persisted_and_audited(): void
    {
        $connection = DicomConnection::factory()->create(['service' => DicomConnection::SERVICE_GET, 'test_enabled' => true]);
        $input = ['confirmed' => false, 'calling_ae_title' => 'NODE_REGISTRY', 'called_ae_title' => 'GET_SCP', 'patient_id' => 'REAL-PATIENT', 'study_instance_uid' => '1.2.3'];
        $admin = $this->administrator();

        $this->actingAs($admin)->post("/tests/get/{$connection->public_id}", $input)->assertSessionHasErrors('confirmed');
        $this->assertDatabaseCount('diagnostic_test_runs', 0);

        $this->app->instance(GetService::class, new GetService(new FakeGetRunner(new GetCommandResult(true, 0, 'status 0x0000', '1.2.826.0.1.3680043.10.987.999.2', 0))));
        $this->actingAs($admin)->post("/tests/get/{$connection->public_id}", [...$input, 'confirmed' => true])->assertSessionHas('success');

        $this->assertDatabaseHas('diagnostic_test_runs', ['dicom_node_id' => $connection->target_dicom_node_id, 'test_type' => 'dicom_get']);
        $this->assertDatabaseHas('security_events', ['event_type' => 'diagnostics.get.completed', 'subject_public_id' => $connection->public_id]);
    }

    public function test_native_runner_cleans_received_files_and_uses_configured_uid(): void
    {
        config(['diagnostics.get_test_study_instance_uid' => '1.2.826.0.1.3680043.10.987.999.24']);
        $node = DicomNode::factory()->create(['host' => '127.0.0.1', 'port' => 1]);
        $before = glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'hnr-get-*') ?: [];

        $result = (new NativeGetCommandRunner)->run($node, 'NODE_REGISTRY', $node->ae_title);

        self::assertSame('1.2.826.0.1.3680043.10.987.999.24', $result->studyInstanceUid);
        self::assertSame($before, glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'hnr-get-*') ?: []);
    }

    private function administrator(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());

        return $user;
    }
}

final readonly class FakeGetRunner implements GetCommandRunner
{
    public function __construct(private GetCommandResult $result) {}

    public function run(DicomNode $targetNode, string $callingAeTitle, string $calledAeTitle): GetCommandResult
    {
        return $this->result;
    }
}
