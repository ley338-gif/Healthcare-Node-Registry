<?php

namespace App\Services\Diagnostics;

final readonly class MoveCommandResult
{
    public function __construct(
        public bool $successful,
        public int $exitCode,
        public string $output,
        public string $studyInstanceUid,
        public bool $timedOut = false,
    ) {}
}
