<?php

namespace App\Services\Diagnostics;

final readonly class CapabilityMatrixResult
{
    /** @param array<int, int> $presentationContextResults */
    public function __construct(
        public bool $associationAccepted,
        public array $presentationContextResults,
        public ?string $error = null,
        public bool $timedOut = false,
    ) {}
}
