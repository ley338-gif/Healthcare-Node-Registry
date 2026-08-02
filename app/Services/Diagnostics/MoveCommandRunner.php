<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;

interface MoveCommandRunner
{
    public function run(
        DicomNode $targetNode,
        DicomNode $destinationNode,
        string $callingAeTitle,
        string $calledAeTitle,
    ): MoveCommandResult;
}
