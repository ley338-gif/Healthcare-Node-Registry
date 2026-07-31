<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;

interface StorageCommandRunner
{
    public function run(DicomNode $node, string $callingAeTitle, string $calledAeTitle): StorageCommandResult;
}
