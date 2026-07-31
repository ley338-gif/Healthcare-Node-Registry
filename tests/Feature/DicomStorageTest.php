<?php

namespace Tests\Feature;

use App\Models\DicomNode;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Diagnostics\DiagnosticTestResult;
use App\Services\Diagnostics\DiagnosticTestStatus;
use App\Services\Diagnostics\DicomStorageTest as StorageService;
use App\Services\Diagnostics\NativeStorageCommandRunner;
use App\Services\Diagnostics\StorageCommandResult;
use App\Services\Diagnostics\StorageCommandRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DicomStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_contains_storage_metadata(): void
    {
        $result = $this->execute(new StorageCommandResult(true, 0, 'Transfer Syntax: Little Endian Explicit; status 0x0000', '1.2.3'));
        self::assertSame(DiagnosticTestStatus::Success, $result->status);
        self::assertSame('1.2.840.10008.5.1.4.1.1.7', $result->details['sopClassUid']);
        self::assertSame('1.2.3', $result->details['sopInstanceUid']);
        self::assertSame('0x0000', $result->details['dimseStatus']);
    }

    public function test_failures_are_classified(): void
    {
        self::assertSame('association_rejected', $this->execute(new StorageCommandResult(false, 1, 'Association Rejected', '1.2.3'))->details['failureType']);
        self::assertSame('sop_class_rejected', $this->execute(new StorageCommandResult(false, 1, 'SOP Class rejected: no presentation context', '1.2.3'))->details['failureType']);
        self::assertSame('transfer_syntax_rejected', $this->execute(new StorageCommandResult(false, 1, 'Transfer Syntax rejected', '1.2.3'))->details['failureType']);
    }

    public function test_confirmation_and_strict_permission_are_required(): void
    {
        $this->seed();
        $node = DicomNode::factory()->create(['supports_store' => true]);
        $manager = User::factory()->create();
        $role = Role::query()->create(['name' => 'registry-manager', 'display_name' => 'Registry Manager']);
        $role->permissions()->attach(Permission::query()->where('name', 'registry.manage')->firstOrFail());
        $manager->roles()->attach($role);

        $this->actingAs($manager)->post("/tests/storage/{$node->public_id}", $this->input($node, true))->assertForbidden();
        $this->actingAs($this->administrator())->post("/tests/storage/{$node->public_id}", $this->input($node, false))->assertSessionHasErrors('confirmed');
    }

    public function test_confirmed_storage_run_is_persisted_and_audited(): void
    {
        $node = DicomNode::factory()->create(['supports_store' => true]);
        $this->app->instance(StorageService::class, new StorageService(new FakeStorageRunner(new StorageCommandResult(true, 0, 'status 0x0000', '1.2.3'))));

        $this->actingAs($this->administrator())->post("/tests/storage/{$node->public_id}", $this->input($node, true))->assertSessionHas('success');

        $this->assertDatabaseHas('diagnostic_test_runs', ['dicom_node_id' => $node->id, 'test_type' => 'dicom_storage']);
        $this->assertDatabaseHas('security_events', ['event_type' => 'diagnostics.storage.completed', 'subject_public_id' => $node->public_id]);
    }

    public function test_native_runner_always_removes_temporary_files(): void
    {
        $node = DicomNode::factory()->create(['host' => '127.0.0.1', 'port' => 1]);
        $before = glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'hnr-store-*') ?: [];
        $result = (new NativeStorageCommandRunner)->run($node, 'NODE_REGISTRY', $node->ae_title);
        self::assertFalse($result->generationFailed);
        self::assertSame($before, glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'hnr-store-*') ?: []);
    }

    private function execute(StorageCommandResult $command): DiagnosticTestResult
    {
        $node = DicomNode::factory()->create(['supports_store' => true]);

        return (new StorageService(new FakeStorageRunner($command)))->run($node, 'NODE_REGISTRY', $node->ae_title);
    }

    /** @return array<string, mixed> */
    private function input(DicomNode $node, bool $confirmed): array
    {
        return ['confirmed' => $confirmed, 'calling_ae_title' => 'NODE_REGISTRY', 'called_ae_title' => $node->ae_title];
    }

    private function administrator(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());

        return $user;
    }
}

final readonly class FakeStorageRunner implements StorageCommandRunner
{
    public function __construct(private StorageCommandResult $result) {}

    public function run(DicomNode $node, string $callingAeTitle, string $calledAeTitle): StorageCommandResult
    {
        return $this->result;
    }
}
