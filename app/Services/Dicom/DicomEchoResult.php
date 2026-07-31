<?php

namespace App\Services\Dicom;

use App\Services\Diagnostics\DiagnosticTarget;
use App\Services\Diagnostics\DiagnosticTestResult;
use App\Services\Diagnostics\DiagnosticTestStatus;
use DateTimeImmutable;
use Illuminate\Support\Str;

final readonly class DicomEchoResult
{
    public DiagnosticTestResult $diagnosticResult;

    public function __construct(
        public bool $successful,
        public string $status,
        public int $durationMilliseconds,
        public string $message,
        public int $exitCode,
        ?DiagnosticTestResult $diagnosticResult = null,
    ) {
        $finishedAt = new DateTimeImmutable;

        $this->diagnosticResult = $diagnosticResult ?? new DiagnosticTestResult(
            testId: (string) Str::uuid7(),
            testType: 'dicom_echo',
            status: match ($status) {
                'success' => DiagnosticTestStatus::Success,
                'timeout' => DiagnosticTestStatus::Timeout,
                default => DiagnosticTestStatus::Failed,
            },
            startedAt: $finishedAt->modify("-{$durationMilliseconds} milliseconds"),
            finishedAt: $finishedAt,
            durationMilliseconds: $durationMilliseconds,
            summary: $message,
            target: new DiagnosticTarget(host: '', port: 0),
            details: ['exitCode' => $exitCode],
            errors: $successful ? [] : [$message],
        );
    }
}
