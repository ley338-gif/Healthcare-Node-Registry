<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;

interface MppsCommandRunner
{
    public function run(DicomNode $node, string $callingAeTitle, string $calledAeTitle): MppsCommandResult;
}
