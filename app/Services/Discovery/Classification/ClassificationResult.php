<?php

namespace App\Services\Discovery\Classification;

final class ClassificationResult
{
    /**
     * @param  list<ClassificationEvidence>  $evidence
     */
    public function __construct(
        public readonly array $evidence,
        public readonly int $percentage,
        public readonly string $confidenceLevel,
        public readonly string $suggestedSystemType,
    ) {}
}
