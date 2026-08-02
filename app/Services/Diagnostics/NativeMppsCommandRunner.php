<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

final class NativeMppsCommandRunner implements MppsCommandRunner
{
    public function run(DicomNode $node, string $callingAeTitle, string $calledAeTitle): MppsCommandResult
    {
        $process = new Process(['/opt/registry-dicom/bin/python', base_path('scripts/dicom_mpps.py')]);
        $process->setInput(json_encode([
            'host' => $node->host,
            'port' => $node->port,
            'calling_ae_title' => $callingAeTitle,
            'called_ae_title' => $calledAeTitle,
        ], JSON_THROW_ON_ERROR));
        $process->setTimeout(30);

        try {
            $process->run();
            $payload = json_decode(trim($process->getOutput()), true, flags: JSON_THROW_ON_ERROR);

            return new MppsCommandResult(
                (bool) ($payload['successful'] ?? false),
                isset($payload['create_status']) ? (int) $payload['create_status'] : null,
                isset($payload['set_status']) ? (int) $payload['set_status'] : null,
                isset($payload['instance_uid']) ? (string) $payload['instance_uid'] : null,
                (string) ($payload['message'] ?? 'MPPS process returned no message.'),
                isset($payload['failure_type']) ? (string) $payload['failure_type'] : null,
            );
        } catch (ProcessTimedOutException $exception) {
            return new MppsCommandResult(false, null, null, null, $exception->getMessage(), 'timeout', true);
        } catch (Throwable $exception) {
            return new MppsCommandResult(false, null, null, null, $exception->getMessage(), 'process_error');
        }
    }
}
