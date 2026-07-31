<?php

namespace App\Services\Dicom;

use App\Models\DicomNode;

interface DicomEchoCommandRunner
{
    public function run(DicomNode $dicomNode): DicomEchoCommandResult;
}
