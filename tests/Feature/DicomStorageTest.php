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
use App\Services\Diagnostics\SyntheticStorageSopClass;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DicomStorageTest extends TestCase
{
    use RefreshDatabase;

    public function test_tls_enabled_success_contains_storage_metadata(): void
    {
        $result = $this->execute(new StorageCommandResult(true, 0, 'Transfer Syntax: Little Endian Explicit; status 0x0000', '1.2.3'));
        self::assertSame(DiagnosticTestStatus::Success, $result->status);
        self::assertSame('1.2.840.10008.5.1.4.1.1.7', $result->details['sopClassUid']);
        self::assertSame('1.2.3', $result->details['sopInstanceUid']);
        self::assertSame('0x0000', $result->details['dimseStatus']);
    }

    public function test_ct_and_mr_storage_classes_are_available_as_synthetic_tests(): void
    {
        $ct = $this->execute(new StorageCommandResult(true, 0, 'status 0x0000', '1.2.3'), SyntheticStorageSopClass::CtImage);
        $mr = $this->execute(new StorageCommandResult(true, 0, 'status 0x0000', '1.2.4'), SyntheticStorageSopClass::MrImage);

        self::assertSame('1.2.840.10008.5.1.4.1.1.2', $ct->details['sopClassUid']);
        self::assertSame('CT Image Storage', $ct->details['sopClass']);
        self::assertSame('1.2.840.10008.5.1.4.1.1.4', $mr->details['sopClassUid']);
        self::assertSame('MR Image Storage', $mr->details['sopClass']);
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
        $node = DicomNode::factory()->create([
            'supports_store' => true,
            'tls_enabled' => true,
            'tls_profile' => 'pacs-production',
        ]);
        $manager = User::factory()->create();
        $role = Role::query()->create(['name' => 'registry-manager', 'display_name' => 'Registry Manager']);
        $role->permissions()->attach(Permission::query()->where('name', 'registry.manage')->firstOrFail());
        $manager->roles()->attach($role);

        $this->actingAs($manager)->post("/tests/storage/{$node->public_id}", $this->input($node, true))->assertForbidden();
        $this->actingAs($this->administrator())->post("/tests/storage/{$node->public_id}", $this->input($node, false))->assertSessionHasErrors('confirmed');
        $this->actingAs($this->administrator())->post("/tests/storage/{$node->public_id}", [...$this->input($node, true), 'sop_class' => 'invalid'])->assertSessionHasErrors('sop_class');
    }

    public function test_confirmed_storage_run_is_persisted_and_audited(): void
    {
        $node = DicomNode::factory()->create(['supports_store' => true]);
        $this->app->instance(StorageService::class, new StorageService(new FakeStorageRunner(new StorageCommandResult(true, 0, 'status 0x0000', '1.2.3'))));

        $this->actingAs($this->administrator())->post("/tests/storage/{$node->public_id}", [...$this->input($node, true), 'sop_class' => 'ct_image'])->assertSessionHas('success');

        $this->assertDatabaseHas('diagnostic_test_runs', ['dicom_node_id' => $node->id, 'test_type' => 'dicom_storage']);
        $this->assertDatabaseHas('security_events', ['event_type' => 'diagnostics.storage.completed', 'subject_public_id' => $node->public_id]);
        $this->assertDatabaseHas('security_events', ['event_type' => 'diagnostics.storage.completed', 'metadata->sop_class_uid' => '1.2.840.10008.5.1.4.1.1.2']);
    }

    public function test_native_runner_generates_every_supported_class_and_always_removes_temporary_files(): void
    {
        $node = DicomNode::factory()->create(['host' => '127.0.0.1', 'port' => 1]);
        $before = glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'hnr-store-*') ?: [];

        foreach (SyntheticStorageSopClass::cases() as $sopClass) {
            $result = (new NativeStorageCommandRunner)->run($node, 'NODE_REGISTRY', $node->ae_title, $sopClass);

            self::assertFalse($result->generationFailed, $sopClass->label());
            self::assertSame($before, glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'hnr-store-*') ?: []);
        }
    }

    private function execute(StorageCommandResult $command, SyntheticStorageSopClass $sopClass = SyntheticStorageSopClass::SecondaryCapture): DiagnosticTestResult
    {
        $node = DicomNode::factory()->create(['supports_store' => true]);

        return (new StorageService(new FakeStorageRunner($command)))->run($node, 'NODE_REGISTRY', $node->ae_title, $sopClass);
    }

    /** @return array<string, mixed> */
    private function input(DicomNode $node, bool $confirmed): array
    {
        return ['confirmed' => $confirmed, 'calling_ae_title' => 'NODE_REGISTRY', 'called_ae_title' => $node->ae_title, 'sop_class' => 'secondary_capture'];
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

    public function run(DicomNode $node, string $callingAeTitle, string $calledAeTitle, SyntheticStorageSopClass $sopClass = SyntheticStorageSopClass::SecondaryCapture): StorageCommandResult
    {
        return $this->result;
    }
}
