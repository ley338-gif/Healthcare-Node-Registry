<?php

namespace App\Services\Diagnostics;

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

final class NativeDicomDumpRunner implements DicomDumpRunner
{
    public function run(string $path): DicomDumpResult
    {
        $process = new Process(['/usr/bin/dcmdump', '+L', '-Un', $path]);
        $process->setTimeout(20);
        try {
            $process->run();

            return new DicomDumpResult($process->isSuccessful(), $process->getExitCode() ?? 1, trim($process->getOutput()."\n".$process->getErrorOutput()));
        } catch (ProcessTimedOutException $exception) {
            return new DicomDumpResult(false, 1, $exception->getMessage(), true);
        }
    }
}
