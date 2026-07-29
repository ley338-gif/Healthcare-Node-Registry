<?php

namespace App\Services\Dicom;

final readonly class DicomEchoResult
{
    public function __construct(
        public bool $successful,
        public string $status,
        public int $durationMilliseconds,
        public string $message,
        public int $exitCode,
    ) {}
}
