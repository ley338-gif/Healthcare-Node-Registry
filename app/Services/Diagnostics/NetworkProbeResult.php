<?php

namespace App\Services\Diagnostics;

final readonly class NetworkProbeResult
{
    public function __construct(
        public bool $connected,
        public int $errorCode = 0,
        public string $errorMessage = '',
    ) {}
}
