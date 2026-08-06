<?php

namespace App\Services\Discovery\Probes;

final class HostProbeResult
{
    public function __construct(
        public readonly bool $reachable,
        public readonly ?int $latencyMs = null,
        public readonly ?string $error = null,
    ) {}
}
