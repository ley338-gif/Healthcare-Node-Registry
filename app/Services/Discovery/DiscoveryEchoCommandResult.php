<?php

namespace App\Services\Discovery;

final class DiscoveryEchoCommandResult
{
    public function __construct(
        public readonly DiscoveryEchoTarget $target,
        public readonly bool $associationSuccessful,
        public readonly bool $echoSuccessful,
        public readonly ?string $errorCode,
        public readonly ?string $errorMessage,
        public readonly string $rawOutput,
        public readonly int $durationMs,
    ) {}
}
