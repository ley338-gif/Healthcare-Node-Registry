<?php

namespace App\Http\Controllers;

use App\Http\Requests\RunWorklistTestRequest;
use App\Models\DicomNode;
use App\Services\Diagnostics\DiagnosticTestRecorder;
use App\Services\Diagnostics\DiagnosticTestStatus;
use App\Services\Diagnostics\WorklistFindParameters;
use App\Services\Diagnostics\WorklistFindTest;
use App\Support\DiagnosticPermission;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class WorklistDiagnosticController extends Controller
{
    public function __invoke(
        RunWorklistTestRequest $request,
        DicomNode $dicomNode,
        WorklistFindTest $test,
        DiagnosticTestRecorder $recorder,
        RegistryAudit $audit,
    ): RedirectResponse {
        Gate::authorize('view', $dicomNode);
        Gate::authorize(DiagnosticPermission::Worklist->value);

        if ($dicomNode->archived_at !== null) {
            return back()->with('error', 'Ein archivierter DICOM-Knoten kann nicht getestet werden.');
        }

        if (! $dicomNode->supports_worklist) {
            return back()->with('error', 'Für diesen DICOM-Knoten ist Worklist nicht aktiviert.');
        }

        $validated = $request->validated();
        $parameters = new WorklistFindParameters(
            callingAeTitle: strtoupper($validated['calling_ae_title']),
            calledAeTitle: strtoupper($validated['called_ae_title']),
            scheduledStationAeTitle: $this->optionalUpper($validated, 'scheduled_station_ae_title'),
            examinationDate: $validated['examination_date'],
            examinationDateTo: $this->optional($validated, 'examination_date_to'),
            modality: $this->optionalUpper($validated, 'modality'),
            patientName: $this->optional($validated, 'patient_name'),
            patientId: $this->optional($validated, 'patient_id'),
            accessionNumber: $this->optional($validated, 'accession_number'),
        );
        $result = $test->run($dicomNode, $parameters);

        DB::transaction(function () use ($request, $dicomNode, $result, $recorder, $audit): void {
            $recorder->record($result, $dicomNode, $request->user());
            $audit->record('diagnostics.worklist.completed', $dicomNode, $request->user(), [
                'test_id' => $result->testId,
                'status' => $result->status->value,
                'duration_milliseconds' => $result->durationMilliseconds,
                'result_count' => $result->details['resultCount'] ?? 0,
                'system_public_id' => $dicomNode->system->public_id,
            ]);
        });

        $response = back()->with('diagnosticResult', $result->toArray());

        return $result->status === DiagnosticTestStatus::Success
            ? $response->with('success', $result->summary)
            : $response->with('error', $result->summary);
    }

    /** @param array<string, mixed> $values */
    private function optional(array $values, string $key): ?string
    {
        $value = $values[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** @param array<string, mixed> $values */
    private function optionalUpper(array $values, string $key): ?string
    {
        $value = $this->optional($values, $key);

        return $value === null ? null : strtoupper($value);
    }
}
