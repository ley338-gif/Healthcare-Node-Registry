<?php

namespace App\Services\Diagnostics;

final readonly class WorklistCommandResult
{
    public function __construct(
        public bool $successful,
        public int $exitCode,
        public string $output,
        public string $xml,
        public bool $timedOut = false,
        public bool $processError = false,
    ) {}
}
