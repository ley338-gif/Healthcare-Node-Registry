<?php

namespace App\Services\Diagnostics;

final readonly class DicomFileAnalysisResult
{
    /**
     * @param  array<string, mixed>  $summary
     * @param  list<string>  $warnings
     * @param  list<string>  $errors
     */
    public function __construct(public bool $successful, public array $summary, public array $warnings, public array $errors, public string $dump) {}

    /** @return array{successful: bool, summary: array<string, mixed>, warnings: list<string>, errors: list<string>, dump: string} */
    public function toArray(): array
    {
        return ['successful' => $this->successful, 'summary' => $this->summary, 'warnings' => $this->warnings, 'errors' => $this->errors, 'dump' => $this->dump];
    }
}
