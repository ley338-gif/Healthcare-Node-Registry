<?php

namespace App\Services\Dicom;

use App\Models\DicomNode;
use App\Services\Diagnostics\DiagnosticTarget;
use App\Services\Diagnostics\DiagnosticTestResult;
use App\Services\Diagnostics\DiagnosticTestStatus;
use DateTimeImmutable;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

class DicomEchoService
{
    public function test(DicomNode $dicomNode): DicomEchoResult
    {
        $startedAt = hrtime(true);
        $startedAtDate = new DateTimeImmutable;

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
                return $this->result(
                    dicomNode: $dicomNode,
                    startedAt: $startedAtDate,
                    successful: true,
                    status: 'success',
                    durationMilliseconds: $durationMilliseconds,
                    message: $output !== ''
                        ? $output
                        : 'C-ECHO erfolgreich.',
                    exitCode: $process->getExitCode() ?? 0,
                );
            }

            return $this->result(
                dicomNode: $dicomNode,
                startedAt: $startedAtDate,
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

            return $this->result(
                dicomNode: $dicomNode,
                startedAt: $startedAtDate,
                successful: false,
                status: 'error',
                durationMilliseconds: $durationMilliseconds,
                message: $exception->getMessage(),
                exitCode: 1,
            );
        }
    }

    private function result(
        DicomNode $dicomNode,
        DateTimeImmutable $startedAt,
        bool $successful,
        string $status,
        int $durationMilliseconds,
        string $message,
        int $exitCode,
    ): DicomEchoResult {
        $finishedAt = new DateTimeImmutable;
        $diagnosticStatus = match ($status) {
            'success' => DiagnosticTestStatus::Success,
            'timeout' => DiagnosticTestStatus::Timeout,
            default => DiagnosticTestStatus::Failed,
        };

        $diagnosticResult = new DiagnosticTestResult(
            testId: (string) Str::uuid7(),
            testType: 'dicom_echo',
            status: $diagnosticStatus,
            startedAt: $startedAt,
            finishedAt: $finishedAt,
            durationMilliseconds: $durationMilliseconds,
            summary: $message,
            target: new DiagnosticTarget(
                host: $dicomNode->host,
                port: $dicomNode->port,
                calledAeTitle: $dicomNode->ae_title,
                callingAeTitle: 'NODE_REGISTRY',
                dicomNodePublicId: $dicomNode->public_id,
                systemPublicId: $dicomNode->system->public_id,
            ),
            details: ['exitCode' => $exitCode],
            errors: $successful ? [] : [$message],
        );

        return new DicomEchoResult(
            successful: $successful,
            status: $status,
            durationMilliseconds: $durationMilliseconds,
            message: $message,
            exitCode: $exitCode,
            diagnosticResult: $diagnosticResult,
        );
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
