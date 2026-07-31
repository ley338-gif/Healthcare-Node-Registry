<?php

namespace App\Services\Diagnostics;

use DateTimeImmutable;

final readonly class DiagnosticTestResult
{
    /**
     * @param  list<DiagnosticTestStep>  $steps
     * @param  array<string, mixed>  $details
     * @param  list<string>  $warnings
     * @param  list<string>  $errors
     */
    public function __construct(
        public string $testId,
        public string $testType,
        public DiagnosticTestStatus $status,
        public DateTimeImmutable $startedAt,
        public DateTimeImmutable $finishedAt,
        public int $durationMilliseconds,
        public string $summary,
        public DiagnosticTarget $target,
        public array $steps = [],
        public array $details = [],
        public array $warnings = [],
        public array $errors = [],
    ) {}

    /**
     * @return array{
     *     testId: string,
     *     testType: string,
     *     status: string,
     *     startedAt: string,
     *     finishedAt: string,
     *     durationMilliseconds: int,
     *     summary: string,
     *     target: array<string, int|string|null>,
     *     steps: list<array<string, mixed>>,
     *     details: array<string, mixed>,
     *     warnings: list<string>,
     *     errors: list<string>
     * }
     */
    public function toArray(): array
    {
        return [
            'testId' => $this->testId,
            'testType' => $this->testType,
            'status' => $this->status->value,
            'startedAt' => $this->startedAt->format(DATE_ATOM),
            'finishedAt' => $this->finishedAt->format(DATE_ATOM),
            'durationMilliseconds' => $this->durationMilliseconds,
            'summary' => $this->summary,
            'target' => $this->target->toArray(),
            'steps' => array_map(
                static fn (DiagnosticTestStep $step): array => $step->toArray(),
                $this->steps,
            ),
            'details' => $this->details,
            'warnings' => $this->warnings,
            'errors' => $this->errors,
        ];
    }
}
