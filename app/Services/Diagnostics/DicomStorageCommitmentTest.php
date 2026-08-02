<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;
use DateTimeImmutable;
use Illuminate\Support\Str;

final class DicomStorageCommitmentTest
{
    private readonly StorageCommandRunner $storageRunner;

    private readonly StorageCommitmentCommandRunner $commitmentRunner;

    public function __construct(?StorageCommandRunner $storageRunner = null, ?StorageCommitmentCommandRunner $commitmentRunner = null)
    {
        $this->storageRunner = $storageRunner ?? new NativeStorageCommandRunner;
        $this->commitmentRunner = $commitmentRunner ?? new NativeStorageCommitmentCommandRunner;
    }

    public function run(DicomNode $node, string $callingAeTitle, string $calledAeTitle): DiagnosticTestResult
    {
        $started = new DateTimeImmutable;
        $timer = hrtime(true);
        $storage = $this->storageRunner->run($node, $callingAeTitle, $calledAeTitle);
        $commitment = $storage->successful
            ? $this->commitmentRunner->run($node, $callingAeTitle, $calledAeTitle, '1.2.840.10008.5.1.4.1.1.7', $storage->sopInstanceUid)
            : new StorageCommitmentCommandResult(false, null, null, null, null, 'C-STORE failed before commitment request.', 'c_store_failed');
        $duration = (int) round((hrtime(true) - $timer) / 1_000_000);
        $status = $commitment->successful ? DiagnosticTestStatus::Success : (in_array($commitment->failureType, ['timeout', 'event_report_timeout'], true) ? DiagnosticTestStatus::Timeout : DiagnosticTestStatus::Failed);
        $summary = $commitment->successful ? 'Storage Commitment für das synthetische Testobjekt bestätigt.' : $this->failureSummary($commitment->failureType);

        return new DiagnosticTestResult(
            testId: (string) Str::uuid7(),
            testType: 'dicom_storage_commitment',
            status: $status,
            startedAt: $started,
            finishedAt: new DateTimeImmutable,
            durationMilliseconds: $duration,
            summary: $summary,
            target: new DiagnosticTarget($node->host, $node->port, $calledAeTitle, $callingAeTitle, $node->public_id, $node->system->public_id),
            steps: [
                new DiagnosticTestStep('authorization', 'Autorisierungsbestätigung', DiagnosticTestStatus::Success, 0, 'Der Benutzer hat den Storage-Commitment-Test ausdrücklich bestätigt.'),
                new DiagnosticTestStep('c_store', 'Synthetisches C-STORE', $storage->successful ? DiagnosticTestStatus::Success : DiagnosticTestStatus::Failed, 0, $storage->successful ? 'Synthetisches Secondary-Capture-Objekt gespeichert.' : 'C-STORE ist fehlgeschlagen.'),
                new DiagnosticTestStep('n_action', 'Storage Commitment N-ACTION', $commitment->actionStatus === 0 ? DiagnosticTestStatus::Success : $status, 0, $commitment->actionStatus === 0 ? 'Commitment-Anfrage angenommen.' : 'N-ACTION wurde nicht erfolgreich bestätigt.'),
                new DiagnosticTestStep('n_event_report', 'Storage Commitment N-EVENT-REPORT', $commitment->successful ? DiagnosticTestStatus::Success : $status, $duration, $commitment->successful ? 'Sichere Verwahrung wurde bestätigt.' : 'Keine erfolgreiche Verwahrungsbestätigung empfangen.'),
            ],
            details: [
                'failureType' => $commitment->failureType,
                'sopClassUid' => '1.2.840.10008.5.1.4.1.1.7',
                'sopInstanceUid' => $storage->sopInstanceUid,
                'transactionUid' => $commitment->transactionUid,
                'nActionStatus' => $this->hex($commitment->actionStatus),
                'eventType' => $commitment->eventType,
                'failureReason' => $commitment->failureReason === null ? null : $this->hex($commitment->failureReason),
                'resultCount' => $commitment->successful ? 1 : 0,
            ],
            warnings: ['Das synthetische Testobjekt wird im Zielsystem gespeichert und dort möglicherweise dauerhaft archiviert.'],
            errors: $commitment->successful ? [] : [$summary],
        );
    }

    private function failureSummary(?string $type): string
    {
        return match ($type) {
            'c_store_failed' => 'Das synthetische Testobjekt konnte vor der Commitment-Anfrage nicht gespeichert werden.',
            'association_rejected' => 'DICOM Association für Storage Commitment wurde abgelehnt.',
            'n_action_failed' => 'Storage Commitment N-ACTION wurde nicht erfolgreich bestätigt.',
            'event_report_timeout', 'timeout' => 'Kein Storage-Commitment-N-EVENT-REPORT innerhalb des Zeitlimits empfangen.',
            'commitment_failed' => 'Das Zielsystem hat die sichere Verwahrung nicht bestätigt.',
            default => 'Storage-Commitment-Test ist technisch fehlgeschlagen.',
        };
    }

    private function hex(?int $status): ?string
    {
        return $status === null ? null : sprintf('0x%04X', $status);
    }
}
