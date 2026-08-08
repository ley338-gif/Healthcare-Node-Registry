<?php

namespace App\Services\Dicom;

use App\Models\DicomNode;
use App\Services\Diagnostics\DiagnosticTarget;
use App\Services\Diagnostics\DiagnosticTestResult;
use App\Services\Diagnostics\DiagnosticTestStatus;
use App\Services\Diagnostics\DiagnosticTestStep;
use DateTimeImmutable;
use Illuminate\Support\Str;

class DicomEchoService
{
    private readonly DicomEchoCommandRunner $runner;

    public function __construct(?DicomEchoCommandRunner $runner = null)
    {
        $this->runner = $runner ?? new NativeDicomEchoCommandRunner;
    }

    public function test(DicomNode $dicomNode): DicomEchoResult
    {
        $startedAt = new DateTimeImmutable;
        $startedAtNanoseconds = hrtime(true);
        $command = $this->runner->run($dicomNode);
        $duration = (int) round((hrtime(true) - $startedAtNanoseconds) / 1_000_000);
        $output = $this->sanitizeOutput($command->output);
        $failureType = $this->failureType($command, $output);
        $status = $command->successful
            ? DiagnosticTestStatus::Success
            : ($failureType === 'timeout' ? DiagnosticTestStatus::Timeout : DiagnosticTestStatus::Failed);
        $summary = $command->successful
            ? 'C-ECHO erfolgreich.'
            : $this->failureSummary($failureType);
        $steps = $this->steps($status, $failureType, $duration, $output);
        $dimseStatus = $this->dimseStatus($output);

        $diagnosticResult = new DiagnosticTestResult(
            testId: (string) Str::uuid7(),
            testType: 'dicom_echo',
            status: $status,
            startedAt: $startedAt,
            finishedAt: new DateTimeImmutable,
            durationMilliseconds: $duration,
            summary: $summary,
            target: new DiagnosticTarget(
                host: $dicomNode->host,
                port: $dicomNode->port,
                calledAeTitle: $dicomNode->ae_title,
                callingAeTitle: (string) config('diagnostics.default_calling_ae_title'),
                dicomNodePublicId: $dicomNode->public_id,
                systemPublicId: $dicomNode->system->public_id,
            ),
            steps: $steps,
            details: [
                'exitCode' => $command->exitCode,
                'dimseStatus' => $dimseStatus,
                'failureType' => $failureType,
                'dcmtkOutput' => $output,
            ],
            errors: $command->successful ? [] : [$summary],
        );

        return new DicomEchoResult(
            successful: $command->successful,
            status: $status->value,
            durationMilliseconds: $duration,
            message: $output !== '' ? $output : $summary,
            exitCode: $command->exitCode,
            diagnosticResult: $diagnosticResult,
        );
    }

    private function failureType(DicomEchoCommandResult $command, string $output): ?string
    {
        $normalized = strtolower($output);

        return match (true) {
            $command->timedOut,
            str_contains($normalized, 'timeout'),
            str_contains($normalized, 'timed out') => 'timeout',
            str_contains($normalized, 'connection refused') => 'connection_refused',
            str_contains($normalized, 'called ae') && str_contains($normalized, 'unknown') => 'called_ae_unknown',
            str_contains($normalized, 'calling ae') && str_contains($normalized, 'not authorized') => 'calling_ae_unauthorized',
            str_contains($normalized, 'association rejected') => 'association_rejected',
            $command->processError => 'process_error',
            $command->successful => null,
            default => 'dicom_failure',
        };
    }

    private function failureSummary(?string $failureType): string
    {
        return match ($failureType) {
            'timeout' => 'Zeitüberschreitung beim C-ECHO.',
            'connection_refused' => 'TCP-Verbindung wurde vom Ziel abgelehnt.',
            'association_rejected' => 'DICOM Association wurde abgelehnt.',
            'called_ae_unknown' => 'Called AE Title ist dem Ziel nicht bekannt.',
            'calling_ae_unauthorized' => 'Calling AE Title ist am Ziel nicht autorisiert.',
            'process_error' => 'DCMTK-Prozess konnte nicht ausgeführt werden.',
            default => 'C-ECHO fehlgeschlagen.',
        };
    }

    /** @return list<DiagnosticTestStep> */
    private function steps(
        DiagnosticTestStatus $status,
        ?string $failureType,
        int $duration,
        string $output,
    ): array {
        $failedAtTcp = in_array($failureType, ['timeout', 'connection_refused'], true);
        $failedAtAssociation = in_array(
            $failureType,
            ['association_rejected', 'called_ae_unknown', 'calling_ae_unauthorized'],
            true,
        );

        return [
            new DiagnosticTestStep(
                'tcp_connection',
                'TCP-Verbindung',
                $failedAtTcp ? $status : DiagnosticTestStatus::Success,
                $failedAtTcp ? $duration : 0,
                $failedAtTcp ? $this->failureSummary($failureType) : 'TCP-Verbindung hergestellt.',
            ),
            new DiagnosticTestStep(
                'dicom_association',
                'DICOM Association',
                $failedAtTcp ? DiagnosticTestStatus::Cancelled : ($failedAtAssociation ? $status : DiagnosticTestStatus::Success),
                $failedAtAssociation ? $duration : 0,
                $failedAtTcp ? 'Nicht ausgeführt.' : ($failedAtAssociation ? $this->failureSummary($failureType) : 'Association akzeptiert.'),
            ),
            new DiagnosticTestStep(
                'verification_sop_class',
                'Verification SOP Class',
                $status === DiagnosticTestStatus::Success ? $status : DiagnosticTestStatus::Cancelled,
                0,
                $status === DiagnosticTestStatus::Success ? 'Verification SOP Class ausgeführt.' : 'Nicht ausgeführt.',
            ),
            new DiagnosticTestStep(
                'dimse_response',
                'DIMSE Response',
                $status === DiagnosticTestStatus::Success ? $status : DiagnosticTestStatus::Cancelled,
                $status === DiagnosticTestStatus::Success ? $duration : 0,
                $status === DiagnosticTestStatus::Success ? 'Erfolgreiche C-ECHO-Antwort empfangen.' : 'Keine erfolgreiche Antwort empfangen.',
                $output === '' ? [] : ['outputAvailable' => true],
            ),
        ];
    }

    private function dimseStatus(string $output): ?string
    {
        preg_match('/(?:DIMSE|status)[^0-9a-f]*(0x[0-9a-f]{4})/i', $output, $matches);

        return $matches[1] ?? null;
    }

    private function sanitizeOutput(string $output): string
    {
        $output = preg_replace(
            '~(?:[A-Z]:\\\\(?:[^\\\\\s]+\\\\)*[^\\\\\s]+)|(?:/(?:[^/\s]+/)+[^/\s]+)~i',
            '[INTERNAL_PATH]',
            $output,
        ) ?? $output;

        return mb_substr(trim($output), 0, 4000);
    }
}
