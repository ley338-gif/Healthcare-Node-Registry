<?php

namespace App\Services\Discovery\Classification;

final class ClassificationContext
{
    /**
     * @param  list<int>  $openDicomCandidatePorts
     * @param  list<array{port: int, called_ae: string}>  $successfulEchoes
     */
    public function __construct(
        public readonly ?string $hostname,
        public readonly array $openDicomCandidatePorts,
        public readonly array $successfulEchoes,
        public readonly bool $existingRegistryMatch,
        public readonly bool $webPortOpen,
        public readonly bool $databasePortOpen,
    ) {}
}
