<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;
use App\Services\Dicom\DicomTlsProfileResolver;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

final class NativeMoveCommandRunner implements MoveCommandRunner
{
    public function __construct(private readonly DicomTlsProfileResolver $tlsProfiles = new DicomTlsProfileResolver) {}

    public function run(
        DicomNode $targetNode,
        DicomNode $destinationNode,
        string $callingAeTitle,
        string $calledAeTitle,
    ): MoveCommandResult {
        $studyInstanceUid = (string) config('diagnostics.move_test_study_instance_uid');
        try {
            $tlsProfile = $this->tlsProfiles->resolve($targetNode);
        } catch (Throwable $exception) {
            return new MoveCommandResult(false, 1, $exception->getMessage(), $studyInstanceUid);
        }

        $process = new Process([
            '/usr/bin/movescu', '-v', '-S',
            '-to', '5', '-ta', '5', '-td', '20',
            '-aet', $callingAeTitle,
            '-aec', $calledAeTitle,
            '-aem', $destinationNode->ae_title,
            '-k', 'QueryRetrieveLevel=STUDY',
            '-k', "StudyInstanceUID={$studyInstanceUid}",
            ...($tlsProfile?->dcmtkArguments() ?? []),
            $targetNode->host,
            (string) $targetNode->port,
        ]);
        $process->setTimeout(30);

        try {
            $process->run();

            return new MoveCommandResult(
                $process->isSuccessful(),
                $process->getExitCode() ?? 1,
                trim($process->getOutput()."\n".$process->getErrorOutput()),
                $studyInstanceUid,
            );
        } catch (ProcessTimedOutException $exception) {
            return new MoveCommandResult(false, 1, $exception->getMessage(), $studyInstanceUid, true);
        } catch (Throwable $exception) {
            return new MoveCommandResult(false, 1, $exception->getMessage(), $studyInstanceUid);
        }
    }
}
