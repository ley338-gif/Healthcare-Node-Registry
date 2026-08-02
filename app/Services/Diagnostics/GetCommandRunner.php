<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;

interface GetCommandRunner
{
    public function run(DicomNode $targetNode, string $callingAeTitle, string $calledAeTitle): GetCommandResult;
}
