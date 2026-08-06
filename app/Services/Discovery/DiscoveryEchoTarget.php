<?php

namespace App\Services\Discovery;

final class DiscoveryEchoTarget
{
    public function __construct(
        public readonly string $host,
        public readonly int $port,
        public readonly string $callingAeTitle,
        public readonly string $calledAeTitle,
    ) {}

    public function key(): string
    {
        return "{$this->host}:{$this->port}:{$this->callingAeTitle}:{$this->calledAeTitle}";
    }
}
