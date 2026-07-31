<?php

namespace App\Services\Diagnostics;

final readonly class StorageCommandResult
{
    public function __construct(
        public bool $successful,
        public int $exitCode,
        public string $output,
        public string $sopInstanceUid,
        public bool $timedOut = false,
        public bool $generationFailed = false,
    ) {}
}
