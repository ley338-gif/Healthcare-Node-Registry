<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;
use DateTimeImmutable;
use Illuminate\Support\Str;

final class DicomStorageTest
{
    private readonly StorageCommandRunner $runner;

    public function __construct(?StorageCommandRunner $runner = null)
    {
        $this->runner = $runner ?? new NativeStorageCommandRunner;
    }

    public function run(DicomNode $node, string $callingAeTitle, string $calledAeTitle): DiagnosticTestResult
    {
        $started = new DateTimeImmutable;
        $timer = hrtime(true);
        $command = $this->runner->run($node, $callingAeTitle, $calledAeTitle);
        $duration = (int) round((hrtime(true) - $timer) / 1_000_000);
        $output = $this->sanitize($command->output);
        $failureType = $this->failureType($command, $output);
        $status = $command->successful
            ? DiagnosticTestStatus::Success
            : ($failureType === 'timeout' ? DiagnosticTestStatus::Timeout : DiagnosticTestStatus::Failed);
        $summary = $command->successful ? 'Synthetisches DICOM-Testobjekt erfolgreich gespeichert.' : $this->failureSummary($failureType);
        $dimseStatus = $this->match('/(?:status|response)[^0-9a-f]*(0x[0-9a-f]{4})/i', $output);
        $transferSyntax = $this->match('/transfer syntax[^\r\n:]*:\s*([^\r\n]+)/i', $output);

        return new DiagnosticTestResult(
            testId: (string) Str::uuid7(), testType: 'dicom_storage', status: $status,
            startedAt: $started, finishedAt: new DateTimeImmutable, durationMilliseconds: $duration,
            summary: $summary,
            target: new DiagnosticTarget($node->host, $node->port, $calledAeTitle, $callingAeTitle, $node->public_id, $node->system->public_id),
            steps: [
                new DiagnosticTestStep('test_object_generation', 'Synthetisches Testobjekt', $command->generationFailed ? DiagnosticTestStatus::Failed : DiagnosticTestStatus::Success, 0, $command->generationFailed ? 'Testobjekt konnte nicht erzeugt werden.' : 'Secondary-Capture-Testobjekt ohne echte Patientendaten erzeugt.'),
                new DiagnosticTestStep('dicom_association', 'DICOM Association', $command->generationFailed ? DiagnosticTestStatus::Cancelled : ($command->successful ? DiagnosticTestStatus::Success : $status), 0, $command->successful ? 'Association akzeptiert.' : $summary),
                new DiagnosticTestStep('c_store', 'C-STORE', $command->successful ? DiagnosticTestStatus::Success : DiagnosticTestStatus::Cancelled, $duration, $command->successful ? 'C-STORE erfolgreich bestätigt.' : 'Keine erfolgreiche C-STORE-Bestätigung.'),
            ],
            details: [
                'exitCode' => $command->exitCode, 'failureType' => $failureType,
                'sopClassUid' => '1.2.840.10008.5.1.4.1.1.7', 'sopClass' => 'Secondary Capture Image Storage',
                'sopInstanceUid' => $command->sopInstanceUid, 'transferSyntax' => $transferSyntax,
                'dimseStatus' => $dimseStatus, 'dcmtkOutput' => $output, 'resultCount' => $command->successful ? 1 : 0,
            ],
            warnings: ['Das Testobjekt kann dauerhaft im Zielsystem gespeichert worden sein.'],
            errors: $command->successful ? [] : [$summary],
        );
    }

    private function failureType(StorageCommandResult $command, string $output): ?string
    {
        $normalized = strtolower($output);

        return match (true) {
            $command->generationFailed => 'generation_failed',
            $command->timedOut, str_contains($normalized, 'timeout'), str_contains($normalized, 'timed out') => 'timeout',
            str_contains($normalized, 'association rejected') => 'association_rejected',
            str_contains($normalized, 'no presentation context') || str_contains($normalized, 'sop class') && str_contains($normalized, 'reject') => 'sop_class_rejected',
            str_contains($normalized, 'transfer syntax') && str_contains($normalized, 'reject') => 'transfer_syntax_rejected',
            default => $command->successful ? null : 'c_store_failed',
        };
    }

    private function failureSummary(?string $type): string
    {
        return match ($type) {
            'generation_failed' => 'Synthetisches DICOM-Testobjekt konnte nicht erzeugt werden.',
            'timeout' => 'Zeitüberschreitung beim DICOM-Storage-Test.',
            'association_rejected' => 'DICOM Association wurde abgelehnt.',
            'sop_class_rejected' => 'Secondary Capture SOP Class wurde abgelehnt.',
            'transfer_syntax_rejected' => 'Transfer Syntax wurde abgelehnt.',
            default => 'C-STORE ist technisch fehlgeschlagen.',
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
