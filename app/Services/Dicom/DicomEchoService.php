<?php

namespace App\Services\Dicom;

use App\Models\DicomNode;
use Symfony\Component\Process\Process;
use Throwable;

class DicomEchoService
{
    public function test(DicomNode $dicomNode): DicomEchoResult
    {
        $startedAt = hrtime(true);

        try {
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
            $process->run();

            $durationMilliseconds = (int) round(
                (hrtime(true) - $startedAt) / 1_000_000,
            );

            $output = trim(
                implode("\n", array_filter([
                    $process->getOutput(),
                    $process->getErrorOutput(),
                ])),
            );

            if ($process->isSuccessful()) {
                return new DicomEchoResult(
                    successful: true,
                    status: 'success',
                    durationMilliseconds: $durationMilliseconds,
                    message: $output !== ''
                        ? $output
                        : 'C-ECHO erfolgreich.',
                    exitCode: $process->getExitCode() ?? 0,
                );
            }

            return new DicomEchoResult(
                successful: false,
                status: $this->detectFailureStatus($output),
                durationMilliseconds: $durationMilliseconds,
                message: $output !== ''
                    ? $output
                    : 'C-ECHO fehlgeschlagen.',
                exitCode: $process->getExitCode() ?? 1,
            );
        } catch (Throwable $exception) {
            $durationMilliseconds = (int) round(
                (hrtime(true) - $startedAt) / 1_000_000,
            );

            return new DicomEchoResult(
                successful: false,
                status: 'error',
                durationMilliseconds: $durationMilliseconds,
                message: $exception->getMessage(),
                exitCode: 1,
            );
        }
    }

    private function detectFailureStatus(string $output): string
    {
        $normalized = strtolower($output);

        if (
            str_contains($normalized, 'timeout')
            || str_contains($normalized, 'timed out')
        ) {
            return 'timeout';
        }

        if (
            str_contains($normalized, 'connection refused')
            || str_contains($normalized, 'association rejected')
        ) {
            return 'unreachable';
        }

        return 'failed';
    }
}
