<?php

namespace Tests\Feature;

use App\Models\DicomNode;
use App\Services\Diagnostics\DiagnosticTestStatus;
use App\Services\Dicom\DicomEchoCommandResult;
use App\Services\Dicom\DicomEchoCommandRunner;
use App\Services\Dicom\DicomEchoResult;
use App\Services\Dicom\DicomEchoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DicomEchoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_contains_all_steps_and_dimse_status(): void
    {
        $result = $this->executeEcho(new DicomEchoCommandResult(true, 0, 'DIMSE status: 0x0000'));

        self::assertTrue($result->successful);
        self::assertSame(DiagnosticTestStatus::Success, $result->diagnosticResult->status);
        self::assertSame(
            ['tcp_connection', 'dicom_association', 'verification_sop_class', 'dimse_response'],
            array_map(static fn ($step): string => $step->name, $result->diagnosticResult->steps),
        );
        self::assertSame('0x0000', $result->diagnosticResult->details['dimseStatus']);
        self::assertSame('NODE_REGISTRY', $result->diagnosticResult->target->callingAeTitle);
    }

    public function test_timeout_is_classified(): void
    {
        $result = $this->executeEcho(new DicomEchoCommandResult(false, 1, 'timed out', timedOut: true));

        self::assertSame(DiagnosticTestStatus::Timeout, $result->diagnosticResult->status);
        self::assertSame('timeout', $result->diagnosticResult->details['failureType']);
    }

    public function test_connection_refused_is_classified(): void
    {
        $this->assertFailure('Connection refused', 'connection_refused');
    }

    public function test_association_rejection_is_classified(): void
    {
        $this->assertFailure('Association Rejected: permanent', 'association_rejected');
    }

    public function test_unknown_called_ae_is_classified(): void
    {
        $this->assertFailure('Association Rejected: Called AE unknown', 'called_ae_unknown');
    }

    public function test_unauthorized_calling_ae_is_classified(): void
    {
        $this->assertFailure('Association Rejected: Calling AE not authorized', 'calling_ae_unauthorized');
    }

    public function test_process_error_is_classified_and_internal_path_is_removed(): void
    {
        $result = $this->executeEcho(new DicomEchoCommandResult(
            false,
            127,
            'Executable C:\\internal\\dcmtk\\echoscu.exe missing',
            processError: true,
        ));

        self::assertSame('process_error', $result->diagnosticResult->details['failureType']);
        self::assertStringNotContainsString('echoscu.exe', $result->message);
    }

    private function assertFailure(string $output, string $expectedType): void
    {
        $result = $this->executeEcho(new DicomEchoCommandResult(false, 1, $output));

        self::assertFalse($result->successful);
        self::assertSame(DiagnosticTestStatus::Failed, $result->diagnosticResult->status);
        self::assertSame($expectedType, $result->diagnosticResult->details['failureType']);
    }

    private function executeEcho(DicomEchoCommandResult $command): DicomEchoResult
    {
        $node = DicomNode::factory()->create([
            'host' => 'pacs.example.test',
            'port' => 104,
            'ae_title' => 'PACS',
        ]);

        return (new DicomEchoService(new FakeDicomEchoCommandRunner($command)))->test($node);
    }
}

final readonly class FakeDicomEchoCommandRunner implements DicomEchoCommandRunner
{
    public function __construct(private DicomEchoCommandResult $result) {}

    public function run(DicomNode $dicomNode): DicomEchoCommandResult
    {
        return $this->result;
    }
}
