<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;

interface WorklistCommandRunner
{
    public function run(DicomNode $node, WorklistFindParameters $parameters): WorklistCommandResult;
}
