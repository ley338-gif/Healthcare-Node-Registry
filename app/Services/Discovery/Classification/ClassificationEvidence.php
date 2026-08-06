<?php

namespace App\Services\Discovery\Classification;

final class ClassificationEvidence
{
    public function __construct(
        public readonly string $ruleName,
        public readonly string $reason,
        public readonly int $weight,
    ) {}
}
