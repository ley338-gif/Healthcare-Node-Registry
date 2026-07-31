<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;

interface CapabilityMatrixRunner
{
    /** @param list<array{id: int, sopClassUid: string, transferSyntaxUid: string}> $contexts */
    public function run(DicomNode $node, string $callingAeTitle, string $calledAeTitle, array $contexts): CapabilityMatrixResult;
}
