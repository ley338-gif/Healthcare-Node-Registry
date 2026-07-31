<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;

final readonly class NativePacsQueryCommandRunner implements PacsQueryCommandRunner
{
    public function __construct(private FindScuCommandExecutor $executor = new FindScuCommandExecutor) {}

    public function run(DicomNode $node, PacsQueryParameters $parameters): WorklistCommandResult
    {
        $values = [
            'PatientName' => $parameters->patientName, 'PatientID' => $parameters->patientId,
            'AccessionNumber' => $parameters->accessionNumber, 'StudyInstanceUID' => $parameters->studyInstanceUid,
            'ModalitiesInStudy' => $parameters->modality, 'StudyDate' => $parameters->dicomDateRange(),
            'StudyDescription' => $parameters->studyDescription,
        ];
        $keys = ['QueryRetrieveLevel=STUDY'];

        foreach ($values as $key => $value) {
            $keys[] = $key.($value === null ? '' : '='.$value);
        }
        $keys = [...$keys, 'NumberOfStudyRelatedSeries', 'NumberOfStudyRelatedInstances'];

        return $this->executor->execute($node, '-S', $parameters->callingAeTitle, $parameters->calledAeTitle, $keys);
    }
}
