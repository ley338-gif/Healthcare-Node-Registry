<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

final class NativeWorklistCommandRunner implements WorklistCommandRunner
{
    public function run(DicomNode $node, WorklistFindParameters $parameters): WorklistCommandResult
    {
        $temporaryDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'hnr-mwl-'.Str::random(20);
        $xmlPath = $temporaryDirectory.DIRECTORY_SEPARATOR.'responses.xml';

        try {
            if (! mkdir($temporaryDirectory, 0700) && ! is_dir($temporaryDirectory)) {
                throw new \RuntimeException('Temporäres Verzeichnis konnte nicht erstellt werden.');
            }

            $process = new Process($this->arguments($node, $parameters, $xmlPath));
            $process->setTimeout(25);
            $process->run();
            $output = trim($process->getOutput()."\n".$process->getErrorOutput());

            return new WorklistCommandResult(
                successful: $process->isSuccessful(),
                exitCode: $process->getExitCode() ?? 1,
                output: $output,
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

            if (is_dir($temporaryDirectory)) {
                rmdir($temporaryDirectory);
            }
        }
    }

    /** @return list<string> */
    private function arguments(
        DicomNode $node,
        WorklistFindParameters $parameters,
        string $xmlPath,
    ): array {
        $arguments = [
            '/usr/bin/findscu', '-W', '-v', '+sr', '-Xs', $xmlPath,
            '--cancel', '100', '-to', '5', '-ts', '15', '-ta', '5', '-td', '15',
            '-aet', $parameters->callingAeTitle,
            '-aec', $parameters->calledAeTitle,
        ];

        foreach ($this->queryKeys($parameters) as $key) {
            $arguments[] = '-k';
            $arguments[] = $key;
        }

        $arguments[] = $node->host;
        $arguments[] = (string) $node->port;

        return $arguments;
    }

    /** @return list<string> */
    private function queryKeys(WorklistFindParameters $parameters): array
    {
        $keys = [
            'PatientName'.($parameters->patientName === null ? '' : '='.$parameters->patientName),
            'PatientID'.($parameters->patientId === null ? '' : '='.$parameters->patientId),
            'PatientBirthDate', 'PatientSex',
            'AccessionNumber'.($parameters->accessionNumber === null ? '' : '='.$parameters->accessionNumber),
            'RequestedProcedureID', 'RequestedProcedureDescription', 'StudyInstanceUID',
            'ScheduledProcedureStepSequence[0].ScheduledStationAETitle'.($parameters->scheduledStationAeTitle === null ? '' : '='.$parameters->scheduledStationAeTitle),
            'ScheduledProcedureStepSequence[0].ScheduledProcedureStepStartDate='.$parameters->dicomDateRange(),
            'ScheduledProcedureStepSequence[0].ScheduledProcedureStepStartTime',
            'ScheduledProcedureStepSequence[0].Modality'.($parameters->modality === null ? '' : '='.$parameters->modality),
            'ScheduledProcedureStepSequence[0].ScheduledProcedureStepDescription',
            'ScheduledProcedureStepSequence[0].ScheduledProcedureStepID',
        ];

        return $keys;
    }
}
