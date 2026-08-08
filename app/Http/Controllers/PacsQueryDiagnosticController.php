<?php

namespace App\Http\Controllers;

use App\Http\Requests\RunPacsQueryTestRequest;
use App\Models\DicomNode;
use App\Services\Diagnostics\DiagnosticTestRecorder;
use App\Services\Diagnostics\DiagnosticTestStatus;
use App\Services\Diagnostics\PacsQueryParameters;
use App\Services\Diagnostics\PacsQueryTest;
use App\Support\DiagnosticPermission;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class PacsQueryDiagnosticController extends Controller
{
    public function __invoke(RunPacsQueryTestRequest $request, DicomNode $dicomNode, PacsQueryTest $test, DiagnosticTestRecorder $recorder, RegistryAudit $audit): RedirectResponse
    {
        Gate::authorize('view', $dicomNode);
        Gate::authorize(DiagnosticPermission::Query->value);
        if ($dicomNode->archived_at !== null) {
            return back()->with('error', 'Ein archivierter DICOM-Knoten kann nicht getestet werden.');
        }
        if (! $dicomNode->supports_query) {
            return back()->with('error', 'Für diesen DICOM-Knoten ist Query nicht aktiviert.');
        }
        $v = $request->validated();
        $get = static fn (string $key): ?string => isset($v[$key]) && is_string($v[$key]) && $v[$key] !== '' ? $v[$key] : null;
        $parameters = new PacsQueryParameters(
            strtoupper($v['calling_ae_title']), strtoupper($v['called_ae_title']), $get('patient_name'), $get('patient_id'),
            $get('accession_number'), $get('study_instance_uid'), ($m = $get('modality')) === null ? null : strtoupper($m),
            $get('study_date'), $get('study_date_to'), $get('study_description'),
        );
        $result = $test->run($dicomNode, $parameters);
        DB::transaction(function () use ($request, $dicomNode, $result, $recorder, $audit): void {
            $recorder->record($result, $dicomNode, $request->user());
            $audit->record('diagnostics.pacs_query.completed', $dicomNode, $request->user(), [
                'test_id' => $result->testId, 'status' => $result->status->value,
                'duration_milliseconds' => $result->durationMilliseconds, 'result_count' => $result->details['resultCount'] ?? 0,
                'system_public_id' => $dicomNode->system->public_id,
            ]);
        });
        $response = back()->with('diagnosticResult', $result->toArray());

        return $result->status === DiagnosticTestStatus::Success ? $response->with('success', $result->summary) : $response->with('error', $result->summary);
    }
}
