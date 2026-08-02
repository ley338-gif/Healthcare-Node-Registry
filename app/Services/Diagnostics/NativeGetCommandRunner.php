<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

final class NativeGetCommandRunner implements GetCommandRunner
{
    public function run(DicomNode $targetNode, string $callingAeTitle, string $calledAeTitle): GetCommandResult
    {
        $studyInstanceUid = (string) config('diagnostics.get_test_study_instance_uid');
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'hnr-get-'.Str::random(20);

        try {
            if (! mkdir($directory, 0700) && ! is_dir($directory)) {
                throw new \RuntimeException('Temporäres C-GET-Verzeichnis konnte nicht erstellt werden.');
            }

            $process = new Process([
                '/usr/bin/getscu', '-v', '-S',
                '-to', '5', '-ta', '5', '-td', '20',
                '-aet', $callingAeTitle,
                '-aec', $calledAeTitle,
                '-od', $directory,
                '-k', 'QueryRetrieveLevel=STUDY',
                '-k', "StudyInstanceUID={$studyInstanceUid}",
                $targetNode->host,
                (string) $targetNode->port,
            ]);
            $process->setTimeout(30);
            $process->run();

            return new GetCommandResult(
                $process->isSuccessful(),
                $process->getExitCode() ?? 1,
                trim($process->getOutput()."\n".$process->getErrorOutput()),
                $studyInstanceUid,
                count(glob($directory.DIRECTORY_SEPARATOR.'*') ?: []),
            );
        } catch (ProcessTimedOutException $exception) {
            return new GetCommandResult(false, 1, $exception->getMessage(), $studyInstanceUid, 0, true);
        } catch (Throwable $exception) {
            return new GetCommandResult(false, 1, $exception->getMessage(), $studyInstanceUid, 0);
        } finally {
            foreach (glob($directory.DIRECTORY_SEPARATOR.'*') ?: [] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }
}
