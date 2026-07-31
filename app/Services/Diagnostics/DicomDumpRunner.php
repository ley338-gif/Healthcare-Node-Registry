<?php

namespace App\Services\Diagnostics;

interface DicomDumpRunner
{
    public function run(string $path): DicomDumpResult;
}
