<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;
use DateTimeImmutable;
use Illuminate\Support\Str;

final class DicomMppsTest
{
    private readonly MppsCommandRunner $runner;

    public function __construct(?MppsCommandRunner $runner = null)
    {
        $this->runner = $runner ?? new NativeMppsCommandRunner;
    }

    public function run(DicomNode $node, string $callingAeTitle, string $calledAeTitle): DiagnosticTestResult
    {
        $started = new DateTimeImmutable;
        $timer = hrtime(true);
        $command = $this->runner->run($node, $callingAeTitle, $calledAeTitle);
        $duration = (int) round((hrtime(true) - $timer) / 1_000_000);
        $status = $command->successful ? DiagnosticTestStatus::Success : ($command->timedOut ? DiagnosticTestStatus::Timeout : DiagnosticTestStatus::Failed);
        $summary = $command->successful ? 'Synthetischer MPPS-Ablauf mit N-CREATE und N-SET erfolgreich.' : $this->failureSummary($command->failureType);

        return new DiagnosticTestResult(
            testId: (string) Str::uuid7(),
            testType: 'dicom_mpps',
            status: $status,
            startedAt: $started,
            finishedAt: new DateTimeImmutable,
            durationMilliseconds: $duration,
            summary: $summary,
            target: new DiagnosticTarget($node->host, $node->port, $calledAeTitle, $callingAeTitle, $node->public_id, $node->system->public_id),
            steps: [
                new DiagnosticTestStep('authorization', 'Autorisierungsbestätigung', DiagnosticTestStatus::Success, 0, 'Der Benutzer hat den synthetischen MPPS-Test ausdrücklich bestätigt.'),
                new DiagnosticTestStep('n_create', 'MPPS N-CREATE', $command->createStatus === 0 ? DiagnosticTestStatus::Success : $status, 0, $command->createStatus === 0 ? 'MPPS-Instanz wurde angelegt.' : 'N-CREATE wurde nicht erfolgreich bestätigt.'),
                new DiagnosticTestStep('n_set', 'MPPS N-SET', $command->setStatus === 0 ? DiagnosticTestStatus::Success : $status, $duration, $command->setStatus === 0 ? 'MPPS-Instanz wurde abgeschlossen.' : 'N-SET wurde nicht erfolgreich bestätigt.'),
            ],
            details: [
                'failureType' => $command->failureType,
                'mppsInstanceUid' => $command->instanceUid,
                'nCreateStatus' => $this->hexStatus($command->createStatus),
                'nSetStatus' => $this->hexStatus($command->setStatus),
                'resultCount' => $command->successful ? 1 : 0,
            ],
            warnings: ['Der Test erzeugt eine synthetische MPPS-Instanz im Zielsystem.'],
            errors: $command->successful ? [] : [$summary],
        );
    }

    private function failureSummary(?string $type): string
    {
        return match ($type) {
            'timeout' => 'Zeitüberschreitung beim MPPS-Test.',
            'association_rejected' => 'DICOM Association für MPPS wurde abgelehnt.',
            'n_create_failed' => 'MPPS N-CREATE wurde vom Zielsystem nicht erfolgreich bestätigt.',
            'n_set_failed' => 'MPPS N-SET wurde vom Zielsystem nicht erfolgreich bestätigt.',
            default => 'MPPS-Test ist technisch fehlgeschlagen.',
        };
    }

    private function hexStatus(?int $status): ?string
    {
        return $status === null ? null : sprintf('0x%04X', $status);
    }
}
