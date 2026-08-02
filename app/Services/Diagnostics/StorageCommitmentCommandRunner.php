<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;

interface StorageCommitmentCommandRunner
{
    public function run(DicomNode $node, string $callingAeTitle, string $calledAeTitle, string $sopClassUid, string $sopInstanceUid): StorageCommitmentCommandResult;
}
