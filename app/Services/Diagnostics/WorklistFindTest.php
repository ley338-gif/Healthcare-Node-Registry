<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;
use DateTimeImmutable;
use Illuminate\Support\Str;
use Throwable;

final class WorklistFindTest
{
    private readonly WorklistCommandRunner $runner;

    public function __construct(
        ?WorklistCommandRunner $runner = null,
        private readonly WorklistResponseParser $parser = new WorklistResponseParser,
    ) {
        $this->runner = $runner ?? new NativeWorklistCommandRunner;
    }

    public function run(
        DicomNode $node,
        WorklistFindParameters $parameters,
    ): DiagnosticTestResult {
        $startedAt = new DateTimeImmutable;
        $startedAtNanoseconds = hrtime(true);
        $command = $this->runner->run($node, $parameters);
        $duration = $this->elapsed($startedAtNanoseconds);
        $output = $this->sanitizeOutput($command->output);
        $failureType = $this->failureType($command, $output);

        if (! $command->successful) {
            $status = $failureType === 'timeout'
                ? DiagnosticTestStatus::Timeout
                : DiagnosticTestStatus::Failed;
            $summary = $this->failureSummary($failureType);

            return $this->result(
                node: $node,
                parameters: $parameters,
                startedAt: $startedAt,
                duration: $duration,
                status: $status,
                summary: $summary,
                steps: $this->failureSteps($status, $failureType, $duration),
                details: [
                    'exitCode' => $command->exitCode,
                    'failureType' => $failureType,
                    'dcmtkOutput' => $output,
                    'resultCount' => 0,
                ],
                errors: [$summary],
            );
        }

        try {
            $items = $this->parser->parse($command->xml);
        } catch (Throwable) {
            return $this->result(
                node: $node,
                parameters: $parameters,
                startedAt: $startedAt,
                duration: $duration,
                status: DiagnosticTestStatus::Failed,
                summary: 'Die Worklist-Antwort konnte nicht verarbeitet werden.',
                steps: $this->failureSteps(DiagnosticTestStatus::Failed, 'parse_error', $duration),
                details: ['exitCode' => $command->exitCode, 'failureType' => 'parse_error', 'resultCount' => 0],
                errors: ['Ungültige Antwortstruktur.'],
            );
        }

        $count = count($items);
        $summary = $count === 0
            ? 'Worklist-Abfrage erfolgreich, keine Treffer.'
            : "Worklist-Abfrage erfolgreich: {$count} Treffer.";

        return $this->result(
            node: $node,
            parameters: $parameters,
            startedAt: $startedAt,
            duration: $duration,
            status: DiagnosticTestStatus::Success,
            summary: $summary,
            steps: [
                new DiagnosticTestStep('dicom_association', 'DICOM Association', DiagnosticTestStatus::Success, 0, 'Association akzeptiert.'),
                new DiagnosticTestStep('c_find', 'MWL C-FIND', DiagnosticTestStatus::Success, $duration, 'C-FIND erfolgreich ausgeführt.'),
                new DiagnosticTestStep('response_processing', 'Antwortverarbeitung', DiagnosticTestStatus::Success, 0, "{$count} Antworten verarbeitet."),
            ],
            details: [
                'exitCode' => $command->exitCode,
                'resultCount' => $count,
                'results' => $items,
            ],
        );
    }

    /**
     * @param  list<DiagnosticTestStep>  $steps
     * @param  array<string, mixed>  $details
     * @param  list<string>  $errors
     */
    private function result(
        DicomNode $node,
        WorklistFindParameters $parameters,
        DateTimeImmutable $startedAt,
        int $duration,
        DiagnosticTestStatus $status,
        string $summary,
        array $steps,
        array $details,
        array $errors = [],
    ): DiagnosticTestResult {
        return new DiagnosticTestResult(
            testId: (string) Str::uuid7(),
            testType: 'worklist',
            status: $status,
            startedAt: $startedAt,
            finishedAt: new DateTimeImmutable,
            durationMilliseconds: $duration,
            summary: $summary,
            target: new DiagnosticTarget(
                host: $node->host,
                port: $node->port,
                calledAeTitle: $parameters->calledAeTitle,
                callingAeTitle: $parameters->callingAeTitle,
                dicomNodePublicId: $node->public_id,
                systemPublicId: $node->system->public_id,
            ),
            steps: $steps,
            details: $details,
            errors: $errors,
        );
    }

    private function failureType(WorklistCommandResult $command, string $output): string
    {
        $normalized = strtolower($output);

        return match (true) {
            $command->timedOut, str_contains($normalized, 'timeout'), str_contains($normalized, 'timed out') => 'timeout',
            str_contains($normalized, 'association rejected') => 'association_rejected',
            str_contains($normalized, 'connection refused') => 'connection_refused',
            $command->processError => 'process_error',
            default => 'c_find_failed',
        };
    }

    private function failureSummary(string $failureType): string
    {
        return match ($failureType) {
            'timeout' => 'Zeitüberschreitung bei der Worklist-Abfrage.',
            'association_rejected' => 'DICOM Association wurde abgelehnt.',
            'connection_refused' => 'TCP-Verbindung wurde vom Ziel abgelehnt.',
            'process_error' => 'DCMTK-Prozess konnte nicht ausgeführt werden.',
            default => 'MWL C-FIND ist technisch fehlgeschlagen.',
        };
    }

    /** @return list<DiagnosticTestStep> */
    private function failureSteps(
        DiagnosticTestStatus $status,
        string $failureType,
        int $duration,
    ): array {
        $associationFailure = in_array($failureType, ['association_rejected', 'timeout', 'connection_refused'], true);

        return [
            new DiagnosticTestStep(
                'dicom_association',
                'DICOM Association',
                $associationFailure ? $status : DiagnosticTestStatus::Success,
                $associationFailure ? $duration : 0,
                $associationFailure ? $this->failureSummary($failureType) : 'Association akzeptiert.',
            ),
            new DiagnosticTestStep(
                'c_find',
                'MWL C-FIND',
                $associationFailure ? DiagnosticTestStatus::Cancelled : $status,
                $associationFailure ? 0 : $duration,
                $associationFailure ? 'Nicht ausgeführt.' : $this->failureSummary($failureType),
            ),
        ];
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

    private function elapsed(int $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1_000_000);
    }
}
