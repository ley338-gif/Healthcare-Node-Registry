<?php

namespace App\Services\Diagnostics;

use App\Models\DicomNode;

final readonly class NativeWorklistCommandRunner implements WorklistCommandRunner
{
    public function __construct(private FindScuCommandExecutor $executor = new FindScuCommandExecutor) {}

    public function run(DicomNode $node, WorklistFindParameters $parameters): WorklistCommandResult
    {
        return $this->executor->execute(
            $node,
            '-W',
            $parameters->callingAeTitle,
            $parameters->calledAeTitle,
            $this->queryKeys($parameters),
        );
    }

    /** @return list<string> */
    private function queryKeys(WorklistFindParameters $parameters): array
    {
        return [
            'PatientName'.($parameters->patientName === null ? '' : '='.$parameters->patientName),
            'PatientID'.($parameters->patientId === null ? '' : '='.$parameters->patientId),
            'PatientBirthDate', 'PatientSex',
            'AccessionNumber'.($parameters->accessionNumber === null ? '' : '='.$parameters->accessionNumber),
            'RequestedProcedureID', 'RequestedProcedureDescription', 'StudyInstanceUID',
            'ScheduledProcedureStepSequence[0].ScheduledStationAETitle'.($parameters->scheduledStationAeTitle === null ? '' : '='.$parameters->scheduledStationAeTitle),
            'ScheduledProcedureStepSequence[0].ScheduledProcedureStepStartDate='.$parameters->dicomDateRange(),
            'ScheduledProcedureStepSequence[0].ScheduledProcedureStepStartTime',
            'ScheduledProcedureStepSequence[0].Modality'.($parameters->modality === null ? '' : '='.$parameters->modality),
            'ScheduledProcedureStepSequence[0].ScheduledProcedureStepDescription',
            'ScheduledProcedureStepSequence[0].ScheduledProcedureStepID',
        ];
    }
}
