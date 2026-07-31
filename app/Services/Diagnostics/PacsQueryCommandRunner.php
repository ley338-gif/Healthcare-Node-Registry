<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;

interface PacsQueryCommandRunner
{
    public function run(DicomNode $node, PacsQueryParameters $parameters): WorklistCommandResult;
}
