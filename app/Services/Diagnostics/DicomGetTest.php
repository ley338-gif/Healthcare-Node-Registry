<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;
use DateTimeImmutable;
use Illuminate\Support\Str;

final class DicomGetTest
{
    private readonly GetCommandRunner $runner;

    public function __construct(?GetCommandRunner $runner = null)
    {
        $this->runner = $runner ?? new NativeGetCommandRunner;
    }

    public function run(DicomNode $targetNode, string $callingAeTitle, string $calledAeTitle): DiagnosticTestResult
    {
        $started = new DateTimeImmutable;
        $timer = hrtime(true);
        $command = $this->runner->run($targetNode, $callingAeTitle, $calledAeTitle);
        $duration = (int) round((hrtime(true) - $timer) / 1_000_000);
        $output = $this->sanitize($command->output);
        $failureType = $this->failureType($command, $output);
        $status = $command->successful ? DiagnosticTestStatus::Success : ($failureType === 'timeout' ? DiagnosticTestStatus::Timeout : DiagnosticTestStatus::Failed);
        $summary = $command->successful ? "C-GET abgeschlossen; {$command->receivedObjectCount} Testobjekt(e) temporär empfangen und gelöscht." : $this->failureSummary($failureType);

        return new DiagnosticTestResult(
            testId: (string) Str::uuid7(), testType: 'dicom_get', status: $status,
            startedAt: $started, finishedAt: new DateTimeImmutable, durationMilliseconds: $duration,
            summary: $summary,
            target: new DiagnosticTarget($targetNode->host, $targetNode->port, $calledAeTitle, $callingAeTitle, $targetNode->public_id, $targetNode->system->public_id),
            steps: [
                new DiagnosticTestStep('authorization', 'Autorisierungsbestätigung', DiagnosticTestStatus::Success, 0, 'Der Benutzer hat den autorisierten Test ausdrücklich bestätigt.'),
                new DiagnosticTestStep('dicom_association', 'DICOM Association', $command->successful ? DiagnosticTestStatus::Success : $status, 0, $command->successful ? 'Association akzeptiert.' : $summary),
                new DiagnosticTestStep('c_get', 'C-GET', $command->successful ? DiagnosticTestStatus::Success : DiagnosticTestStatus::Failed, $duration, $command->successful ? 'C-GET-Antwort verarbeitet und temporäre Objekte bereinigt.' : 'Keine erfolgreiche C-GET-Bestätigung.'),
            ],
            details: [
                'exitCode' => $command->exitCode, 'failureType' => $failureType,
                'studyInstanceUid' => $command->studyInstanceUid,
                'receivedObjectCount' => $command->receivedObjectCount,
                'dimseStatus' => $this->match('/(?:status|response)[^0-9a-f]*(0x[0-9a-f]{4})/i', $output),
                'dcmtkOutput' => $output, 'resultCount' => $command->receivedObjectCount,
            ],
            warnings: ['Empfangene Testobjekte werden ausschließlich temporär gespeichert und unmittelbar nach dem Lauf gelöscht.'],
            errors: $command->successful ? [] : [$summary],
        );
    }

    private function failureType(GetCommandResult $command, string $output): ?string
    {
        $normalized = strtolower($output);

        return match (true) {
            $command->timedOut, str_contains($normalized, 'timeout'), str_contains($normalized, 'timed out') => 'timeout',
            str_contains($normalized, 'association rejected') => 'association_rejected',
            str_contains($normalized, 'no presentation context') => 'presentation_context_rejected',
            default => $command->successful ? null : 'c_get_failed',
        };
    }

    private function failureSummary(?string $type): string
    {
        return match ($type) {
            'timeout' => 'Zeitüberschreitung beim C-GET-Test.',
            'association_rejected' => 'DICOM Association wurde abgelehnt.',
            'presentation_context_rejected' => 'Der benötigte C-GET-Presentation-Context wurde abgelehnt.',
            default => 'C-GET ist technisch fehlgeschlagen.',
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
