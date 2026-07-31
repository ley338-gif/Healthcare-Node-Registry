<?php

namespace App\Services\Dicom;

final readonly class DicomEchoCommandResult
{
    public function __construct(
        public bool $successful,
        public int $exitCode,
        public string $output,
        public bool $timedOut = false,
        public bool $processError = false,
    ) {}
}
