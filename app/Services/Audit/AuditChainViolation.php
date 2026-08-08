<?php

namespace App\Services\Audit;

use Carbon\CarbonImmutable;
use RuntimeException;

final class AuditChainViolation extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $databaseId = null,
        public readonly ?string $eventId = null,
        public readonly ?CarbonImmutable $occurredAt = null,
    ) {
        parent::__construct($message);
    }
}
