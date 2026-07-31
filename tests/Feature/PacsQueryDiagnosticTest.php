<?php

namespace Tests\Feature;

use App\Models\DicomNode;
use App\Models\Role;
use App\Models\User;
use App\Services\Diagnostics\DiagnosticTestResult;
use App\Services\Diagnostics\PacsQueryCommandRunner;
use App\Services\Diagnostics\PacsQueryParameters;
use App\Services\Diagnostics\PacsQueryTest;
use App\Services\Diagnostics\WorklistCommandResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PacsQueryDiagnosticTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_with_results_is_normalized(): void
    {
        $result = $this->execute(new WorklistCommandResult(true, 0, '', '<responses><data-set><element tag="0010,0010">DOE^JANE</element><element tag="0020,000D">1.2.3</element><element tag="0020,1206">3</element><element tag="0020,1208">42</element></data-set></responses>'));
        self::assertSame(1, $result->details['resultCount']);
        self::assertSame('1.2.3', $result->details['results'][0]['studyInstanceUid']);
        self::assertSame(3, $result->details['results'][0]['seriesCount']);
        self::assertSame(42, $result->details['results'][0]['instanceCount']);
    }

    public function test_success_without_results_is_not_failure(): void
    {
        self::assertSame('success', $this->execute(new WorklistCommandResult(true, 0, '', '<responses/>'))->status->value);
    }

    public function test_association_rejection_and_timeout_are_classified(): void
    {
        self::assertSame('association_rejected', $this->execute(new WorklistCommandResult(false, 1, 'Association Rejected', ''))->details['failureType']);
        self::assertSame('timeout', $this->execute(new WorklistCommandResult(false, 1, 'timed out', '', timedOut: true))->status->value);
    }

    public function test_invalid_uid_is_rejected_and_unprivileged_user_is_forbidden(): void
    {
        $node = DicomNode::factory()->create(['supports_query' => true]);
        $input = $this->input($node);
        $input['study_instance_uid'] = 'invalid-uid';
        $this->actingAs($this->manager())->post("/tests/pacs-query/{$node->public_id}", $input)->assertSessionHasErrors('study_instance_uid');
        $this->actingAs(User::factory()->create())->post("/tests/pacs-query/{$node->public_id}", $this->input($node))->assertForbidden();
    }

    public function test_authorized_query_is_persisted(): void
    {
        $node = DicomNode::factory()->create(['supports_query' => true]);
        $this->app->instance(PacsQueryTest::class, new PacsQueryTest(new FakePacsRunner(new WorklistCommandResult(true, 0, '', '<responses/>'))));
        $this->actingAs($this->manager())->post("/tests/pacs-query/{$node->public_id}", $this->input($node))->assertSessionHas('success');
        $this->assertDatabaseHas('diagnostic_test_runs', ['dicom_node_id' => $node->id, 'test_type' => 'pacs_query']);
    }

    private function execute(WorklistCommandResult $command): DiagnosticTestResult
    {
        $node = DicomNode::factory()->create(['supports_query' => true]);

        return (new PacsQueryTest(new FakePacsRunner($command)))->run($node, new PacsQueryParameters('NODE_REGISTRY', $node->ae_title, null, null, null, null, null, null, null, null));
    }

    /** @return array<string, string|null> */
    private function input(DicomNode $node): array
    {
        return ['calling_ae_title' => 'NODE_REGISTRY', 'called_ae_title' => $node->ae_title, 'patient_name' => null, 'patient_id' => null, 'accession_number' => null, 'study_instance_uid' => null, 'modality' => null, 'study_date' => null, 'study_date_to' => null, 'study_description' => null];
    }

    private function manager(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());

        return $user;
    }
}

final readonly class FakePacsRunner implements PacsQueryCommandRunner
{
    public function __construct(private WorklistCommandResult $result) {}

    public function run(DicomNode $node, PacsQueryParameters $parameters): WorklistCommandResult
    {
        return $this->result;
    }
}
