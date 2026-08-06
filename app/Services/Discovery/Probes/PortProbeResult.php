<?php

namespace App\Services\Discovery\Probes;

final class PortProbeResult
{
    public function __construct(
        public readonly bool $open,
        public readonly ?int $responseTimeMs = null,
    ) {}
}
