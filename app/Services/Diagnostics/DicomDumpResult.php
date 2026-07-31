<?php

namespace App\Services\Diagnostics;

final readonly class DicomDumpResult
{
    public function __construct(public bool $successful, public int $exitCode, public string $output, public bool $timedOut = false) {}
}
