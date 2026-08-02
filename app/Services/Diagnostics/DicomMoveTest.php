<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;
use DateTimeImmutable;
use Illuminate\Support\Str;

final class DicomMoveTest
{
    private readonly MoveCommandRunner $runner;

    public function __construct(?MoveCommandRunner $runner = null)
    {
        $this->runner = $runner ?? new NativeMoveCommandRunner;
    }

    public function run(
        DicomNode $targetNode,
        DicomNode $destinationNode,
        string $callingAeTitle,
        string $calledAeTitle,
    ): DiagnosticTestResult {
        $started = new DateTimeImmutable;
        $timer = hrtime(true);
        $command = $this->runner->run($targetNode, $destinationNode, $callingAeTitle, $calledAeTitle);
        $duration = (int) round((hrtime(true) - $timer) / 1_000_000);
        $output = $this->sanitize($command->output);
        $failureType = $this->failureType($command, $output);
        $status = $command->successful
            ? DiagnosticTestStatus::Success
            : ($failureType === 'timeout' ? DiagnosticTestStatus::Timeout : DiagnosticTestStatus::Failed);
        $summary = $command->successful
            ? 'C-MOVE für die konfigurierte synthetische Test-Study-UID wurde erfolgreich abgeschlossen.'
            : $this->failureSummary($failureType);

        return new DiagnosticTestResult(
            testId: (string) Str::uuid7(),
            testType: 'dicom_move',
            status: $status,
            startedAt: $started,
            finishedAt: new DateTimeImmutable,
            durationMilliseconds: $duration,
            summary: $summary,
            target: new DiagnosticTarget($targetNode->host, $targetNode->port, $calledAeTitle, $callingAeTitle, $targetNode->public_id, $targetNode->system->public_id),
            steps: [
                new DiagnosticTestStep('authorization', 'Autorisierungsbestätigung', DiagnosticTestStatus::Success, 0, 'Der Benutzer hat den autorisierten Test ausdrücklich bestätigt.'),
                new DiagnosticTestStep('dicom_association', 'DICOM Association', $command->successful ? DiagnosticTestStatus::Success : $status, 0, $command->successful ? 'Association akzeptiert.' : $summary),
                new DiagnosticTestStep('c_move', 'C-MOVE', $command->successful ? DiagnosticTestStatus::Success : DiagnosticTestStatus::Failed, $duration, $command->successful ? 'C-MOVE-Antwort erfolgreich verarbeitet.' : 'Keine erfolgreiche C-MOVE-Bestätigung.'),
            ],
            details: [
                'exitCode' => $command->exitCode,
                'failureType' => $failureType,
                'studyInstanceUid' => $command->studyInstanceUid,
                'destinationNodePublicId' => $destinationNode->public_id,
                'destinationAeTitle' => $destinationNode->ae_title,
                'dimseStatus' => $this->match('/(?:status|response)[^0-9a-f]*(0x[0-9a-f]{4})/i', $output),
                'dcmtkOutput' => $output,
                'resultCount' => $command->successful ? 1 : 0,
            ],
            warnings: ['Dieser Test kann einen realen Transfer auslösen, wenn die konfigurierte Test-UID im Zielsystem existiert.'],
            errors: $command->successful ? [] : [$summary],
        );
    }

    private function failureType(MoveCommandResult $command, string $output): ?string
    {
        $normalized = strtolower($output);

        return match (true) {
            $command->timedOut, str_contains($normalized, 'timeout'), str_contains($normalized, 'timed out') => 'timeout',
            str_contains($normalized, 'association rejected') => 'association_rejected',
            str_contains($normalized, 'unknown move destination') => 'unknown_move_destination',
            default => $command->successful ? null : 'c_move_failed',
        };
    }

    private function failureSummary(?string $type): string
    {
        return match ($type) {
            'timeout' => 'Zeitüberschreitung beim C-MOVE-Test.',
            'association_rejected' => 'DICOM Association wurde abgelehnt.',
            'unknown_move_destination' => 'Das Zielsystem kennt den konfigurierten Move-Destination-AE-Title nicht.',
            default => 'C-MOVE ist technisch fehlgeschlagen.',
        };
    }

    private function sanitize(string $output): string
    {
        $output = preg_replace('~(?:[A-Z]:\\\\(?:[^\\\\\s]+\\\\)*[^\\\\\s]+)|(?:/(?:[^/\s]+/)+[^/\s]+)~i', '[INTERNAL_PATH]', $output) ?? $output;

        return mb_substr(trim($output), 0, 4000);
    }

    private function match(string $pattern, string $output): ?string
    {
        preg_match($pattern, $output, $matches);

        return $matches[1] ?? null;
    }
}
