<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;
use App\Services\Dicom\DicomTlsProfileResolver;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

final class NativeStorageCommitmentCommandRunner implements StorageCommitmentCommandRunner
{
    public function __construct(private readonly DicomTlsProfileResolver $tlsProfiles = new DicomTlsProfileResolver) {}

    public function run(DicomNode $node, string $callingAeTitle, string $calledAeTitle, string $sopClassUid, string $sopInstanceUid): StorageCommitmentCommandResult
    {
        $eventTimeout = (int) config('diagnostics.storage_commitment_event_timeout_seconds');
        try {
            $tlsProfile = $this->tlsProfiles->resolve($node);
        } catch (Throwable $exception) {
            return new StorageCommitmentCommandResult(false, null, null, null, null, $exception->getMessage(), 'tls_configuration');
        }

        $process = new Process(['/opt/registry-dicom/bin/python', base_path('scripts/dicom_storage_commitment.py')]);
        $process->setInput(json_encode([
            'host' => $node->host,
            'port' => $node->port,
            'calling_ae_title' => $callingAeTitle,
            'called_ae_title' => $calledAeTitle,
            'sop_class_uid' => $sopClassUid,
            'sop_instance_uid' => $sopInstanceUid,
            'callback_bind' => '0.0.0.0',
            'callback_port' => (int) config('diagnostics.storage_commitment_callback_port'),
            'event_timeout_seconds' => $eventTimeout,
            'tls' => $tlsProfile?->pythonPayload(),
        ], JSON_THROW_ON_ERROR));
        $process->setTimeout($eventTimeout + 15);

        try {
            $process->run();
            $payload = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR);

            return new StorageCommitmentCommandResult(
                (bool) ($payload['successful'] ?? false),
                isset($payload['action_status']) ? (int) $payload['action_status'] : null,
                isset($payload['event_type']) ? (int) $payload['event_type'] : null,
                isset($payload['transaction_uid']) ? (string) $payload['transaction_uid'] : null,
                isset($payload['failure_reason']) ? (int) $payload['failure_reason'] : null,
                (string) ($payload['message'] ?? 'Storage Commitment process returned no message.'),
                isset($payload['failure_type']) ? (string) $payload['failure_type'] : null,
            );
        } catch (ProcessTimedOutException $exception) {
            return new StorageCommitmentCommandResult(false, null, null, null, null, $exception->getMessage(), 'timeout');
        } catch (Throwable $exception) {
            return new StorageCommitmentCommandResult(false, null, null, null, null, $exception->getMessage(), 'process_error');
        }
    }
}
