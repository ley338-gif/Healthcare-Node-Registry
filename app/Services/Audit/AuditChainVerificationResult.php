<?php

namespace App\Services\Audit;

final readonly class AuditChainVerificationResult
{
    public function __construct(
        public int $verifiedEvents,
        public string $headHash,
        public ?int $prunedThroughEventId,
    ) {}
}
