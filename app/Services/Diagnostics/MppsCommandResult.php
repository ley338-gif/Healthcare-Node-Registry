<?php

namespace App\Services\Diagnostics;

final readonly class MppsCommandResult
{
    public function __construct(
        public bool $successful,
        public ?int $createStatus,
        public ?int $setStatus,
        public ?string $instanceUid,
        public string $message,
        public ?string $failureType = null,
        public bool $timedOut = false,
    ) {}
}
