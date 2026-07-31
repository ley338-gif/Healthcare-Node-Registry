<?php

namespace App\Services\Dicom;

use App\Models\DicomNode;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

final class NativeDicomEchoCommandRunner implements DicomEchoCommandRunner
{
    public function run(DicomNode $dicomNode): DicomEchoCommandResult
    {
        $process = new Process([
            '/usr/bin/echoscu',
            '-v',
            '-aet',
            'NODE_REGISTRY',
            '-aec',
            $dicomNode->ae_title,
            '-to',
            '5',
            '-ta',
            '5',
            '-td',
            '5',
            $dicomNode->host,
            (string) $dicomNode->port,
        ]);
        $process->setTimeout(15);

        try {
            $process->run();

            return new DicomEchoCommandResult(
                successful: $process->isSuccessful(),
                exitCode: $process->getExitCode() ?? 1,
                output: trim($process->getOutput()."\n".$process->getErrorOutput()),
            );
        } catch (ProcessTimedOutException $exception) {
            return new DicomEchoCommandResult(false, 1, $exception->getMessage(), timedOut: true);
        } catch (Throwable $exception) {
            return new DicomEchoCommandResult(false, 1, $exception->getMessage(), processError: true);
        }
    }
}
