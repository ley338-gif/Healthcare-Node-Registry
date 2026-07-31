<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

final class FindScuCommandExecutor
{
    /** @param list<string> $keys */
    public function execute(
        DicomNode $node,
        string $queryModel,
        string $callingAeTitle,
        string $calledAeTitle,
        array $keys,
    ): WorklistCommandResult {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'hnr-find-'.Str::random(20);
        $xmlPath = $directory.DIRECTORY_SEPARATOR.'responses.xml';

        try {
            if (! mkdir($directory, 0700) && ! is_dir($directory)) {
                throw new \RuntimeException('Temporäres Verzeichnis konnte nicht erstellt werden.');
            }

            $arguments = [
                '/usr/bin/findscu', $queryModel, '-v', '+sr', '-Xs', $xmlPath,
                '--cancel', '100', '-to', '5', '-ts', '15', '-ta', '5', '-td', '15',
                '-aet', $callingAeTitle, '-aec', $calledAeTitle,
            ];

            foreach ($keys as $key) {
                $arguments[] = '-k';
                $arguments[] = $key;
            }

            $arguments[] = $node->host;
            $arguments[] = (string) $node->port;
            $process = new Process($arguments);
            $process->setTimeout(25);
            $process->run();

            return new WorklistCommandResult(
                successful: $process->isSuccessful(),
                exitCode: $process->getExitCode() ?? 1,
                output: trim($process->getOutput()."\n".$process->getErrorOutput()),
                xml: is_file($xmlPath) ? (string) file_get_contents($xmlPath) : '',
            );
        } catch (ProcessTimedOutException $exception) {
            return new WorklistCommandResult(false, 1, $exception->getMessage(), '', timedOut: true);
        } catch (Throwable $exception) {
            return new WorklistCommandResult(false, 1, $exception->getMessage(), '', processError: true);
        } finally {
            if (is_file($xmlPath)) {
                unlink($xmlPath);
            }

            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }
}
