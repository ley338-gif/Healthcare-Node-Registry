<?php

namespace App\Services\Diagnostics;

final readonly class StorageCommitmentCommandResult
{
    public function __construct(
        public bool $successful,
        public ?int $actionStatus,
        public ?int $eventType,
        public ?string $transactionUid,
        public ?int $failureReason,
        public string $message,
        public ?string $failureType = null,
    ) {}
}
