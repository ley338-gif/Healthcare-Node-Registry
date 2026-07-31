<?php

namespace App\Services\Diagnostics;

final readonly class DiagnosticTarget
{
    public function __construct(
        public string $host,
        public int $port,
        public ?string $calledAeTitle = null,
        public ?string $callingAeTitle = null,
        public ?string $dicomNodePublicId = null,
        public ?string $systemPublicId = null,
    ) {}

    /**
     * @return array{
     *     host: string,
     *     port: int,
     *     calledAeTitle: string|null,
     *     callingAeTitle: string|null,
     *     dicomNodePublicId: string|null,
     *     systemPublicId: string|null
     * }
     */
    public function toArray(): array
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'calledAeTitle' => $this->calledAeTitle,
            'callingAeTitle' => $this->callingAeTitle,
            'dicomNodePublicId' => $this->dicomNodePublicId,
            'systemPublicId' => $this->systemPublicId,
        ];
    }
}
