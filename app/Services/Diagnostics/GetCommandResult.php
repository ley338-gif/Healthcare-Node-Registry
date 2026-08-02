<?php

namespace App\Services\Diagnostics;

final readonly class GetCommandResult
{
    public function __construct(
        public bool $successful,
        public int $exitCode,
        public string $output,
        public string $studyInstanceUid,
        public int $receivedObjectCount,
        public bool $timedOut = false,
    ) {}
}
