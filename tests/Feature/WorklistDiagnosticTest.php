<?php

namespace Tests\Feature;

use App\Models\DicomNode;
use App\Models\Role;
use App\Models\User;
use App\Services\Diagnostics\DiagnosticTestResult;
use App\Services\Diagnostics\DiagnosticTestStatus;
use App\Services\Diagnostics\NativeWorklistCommandRunner;
use App\Services\Diagnostics\WorklistCommandResult;
use App\Services\Diagnostics\WorklistCommandRunner;
use App\Services\Diagnostics\WorklistFindParameters;
use App\Services\Diagnostics\WorklistFindTest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class WorklistDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_query_without_results_is_not_an_error(): void
    {
        $result = $this->executeWorklist(new WorklistCommandResult(true, 0, '', '<responses/>'));

        self::assertSame(DiagnosticTestStatus::Success, $result->status);
        self::assertSame(0, $result->details['resultCount']);
        self::assertSame([], $result->details['results']);
    }

    public function test_successful_query_parses_multiple_results_and_sequence_attributes(): void
    {
        $result = $this->executeWorklist(new WorklistCommandResult(true, 0, '', $this->responseXml()));

        self::assertSame(DiagnosticTestStatus::Success, $result->status);
        self::assertSame(2, $result->details['resultCount']);
        self::assertSame('DOE^JANE', $result->details['results'][0]['patientName']);
        self::assertSame('CT01', $result->details['results'][0]['scheduledStationAeTitle']);
        self::assertSame('SPS-1', $result->details['results'][0]['scheduledProcedureStepId']);
    }

    public function test_association_rejection_is_classified(): void
    {
        $result = $this->executeWorklist(new WorklistCommandResult(false, 1, 'Association Rejected', ''));

        self::assertSame('association_rejected', $result->details['failureType']);
        self::assertSame(DiagnosticTestStatus::Failed, $result->status);
    }

    public function test_timeout_is_classified(): void
    {
        $result = $this->executeWorklist(new WorklistCommandResult(false, 1, 'timed out', '', timedOut: true));

        self::assertSame('timeout', $result->details['failureType']);
        self::assertSame(DiagnosticTestStatus::Timeout, $result->status);
    }

    public function test_authorized_user_can_run_worklist_and_history_masks_patient_data(): void
    {
        $user = $this->createRegistryManager();
        $node = DicomNode::factory()->create(['supports_worklist' => true, 'ae_title' => 'MWL_SCP']);
        $this->useCommand(new WorklistCommandResult(true, 0, '', $this->responseXml()));

        $this->actingAs($user)
            ->post("/tests/worklist/{$node->public_id}", $this->validInput($node))
            ->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionHas('diagnosticResult.details.resultCount', 2);

        $run = $node->diagnosticTestRuns()->where('test_type', 'worklist')->firstOrFail();
        self::assertSame(2, $run->result_count);
        self::assertSame('[REDACTED]', $run->details['results'][0]['patientName']);
        self::assertSame('[REDACTED]', $run->details['results'][0]['patientId']);
        $this->assertDatabaseHas('security_events', [
            'event_type' => 'diagnostics.worklist.completed',
            'subject_public_id' => $node->public_id,
        ]);
    }

    public function test_invalid_filters_are_rejected_before_execution(): void
    {
        $user = $this->createRegistryManager();
        $node = DicomNode::factory()->create(['supports_worklist' => true]);
        $runner = new FakeWorklistCommandRunner(new WorklistCommandResult(true, 0, '', '<responses/>'));
        $this->app->instance(WorklistFindTest::class, new WorklistFindTest($runner));

        $this->actingAs($user)
            ->from('/tests')
            ->post("/tests/worklist/{$node->public_id}", [
                ...$this->validInput($node),
                'examination_date' => 'not-a-date',
                'calling_ae_title' => 'INVALID/AE',
            ])
            ->assertRedirect('/tests')
            ->assertSessionHasErrors(['examination_date', 'calling_ae_title']);

        self::assertSame(0, $runner->runs);
    }

    public function test_unprivileged_user_cannot_run_worklist(): void
    {
        $node = DicomNode::factory()->create(['supports_worklist' => true]);

        $this->actingAs(User::factory()->create())
            ->post("/tests/worklist/{$node->public_id}", $this->validInput($node))
            ->assertForbidden();
    }

    public function test_node_without_worklist_support_is_not_executed(): void
    {
        $user = $this->createRegistryManager();
        $node = DicomNode::factory()->create(['supports_worklist' => false]);
        $runner = new FakeWorklistCommandRunner(new WorklistCommandResult(true, 0, '', '<responses/>'));
        $this->app->instance(WorklistFindTest::class, new WorklistFindTest($runner));

        $this->actingAs($user)
            ->post("/tests/worklist/{$node->public_id}", $this->validInput($node))
            ->assertSessionHas('error');

        self::assertSame(0, $runner->runs);
    }

    public function test_native_runner_removes_its_temporary_directory(): void
    {
        $node = DicomNode::factory()->create([
            'host' => '127.0.0.1',
            'port' => 1,
            'supports_worklist' => true,
        ]);
        $before = glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'hnr-mwl-*') ?: [];

        (new NativeWorklistCommandRunner)->run($node, $this->parameters($node));

        self::assertSame($before, glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'hnr-mwl-*') ?: []);
    }

    private function executeWorklist(WorklistCommandResult $command): DiagnosticTestResult
    {
        $node = DicomNode::factory()->create(['supports_worklist' => true]);

        return (new WorklistFindTest(new FakeWorklistCommandRunner($command)))
            ->run($node, $this->parameters($node));
    }

    private function useCommand(WorklistCommandResult $command): void
    {
        $this->app->instance(
            WorklistFindTest::class,
            new WorklistFindTest(new FakeWorklistCommandRunner($command)),
        );
    }

    /** @return array<string, string|null> */
    private function validInput(DicomNode $node): array
    {
        return [
            'calling_ae_title' => 'NODE_REGISTRY',
            'called_ae_title' => $node->ae_title,
            'scheduled_station_ae_title' => 'CT01',
            'examination_date' => '2026-07-31',
            'examination_date_to' => null,
            'modality' => 'CT',
            'patient_name' => null,
            'patient_id' => null,
            'accession_number' => null,
        ];
    }

    private function parameters(DicomNode $node): WorklistFindParameters
    {
        return new WorklistFindParameters(
            'NODE_REGISTRY',
            $node->ae_title,
            'CT01',
            '2026-07-31',
            null,
            'CT',
            null,
            null,
            null,
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

    private function responseXml(): string
    {
        return <<<'XML'
<?xml version="1.0"?>
<responses>
  <data-set>
    <element tag="0010,0010">DOE^JANE</element><element tag="0010,0020">P-1</element>
    <sequence tag="0040,0100"><item><element tag="0040,0001">CT01</element><element tag="0040,0002">20260731</element><element tag="0040,0003">083000</element><element tag="0008,0060">CT</element><element tag="0040,0009">SPS-1</element></item></sequence>
  </data-set>
  <data-set><element tag="0010,0010">DOE^JOHN</element><element tag="0010,0020">P-2</element></data-set>
</responses>
XML;
    }
}

final class FakeWorklistCommandRunner implements WorklistCommandRunner
{
    public int $runs = 0;

    public function __construct(private readonly WorklistCommandResult $result) {}

    public function run(DicomNode $node, WorklistFindParameters $parameters): WorklistCommandResult
    {
        $this->runs++;

        return $this->result;
    }
}
