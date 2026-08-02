<?php

namespace App\Http\Controllers;

use App\Http\Requests\RunMppsTestRequest;
use App\Models\DicomNode;
use App\Services\Diagnostics\DiagnosticTestRecorder;
use App\Services\Diagnostics\DiagnosticTestStatus;
use App\Services\Diagnostics\DicomMppsTest;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class MppsDiagnosticController extends Controller
{
    public function __invoke(RunMppsTestRequest $request, DicomNode $dicomNode, DicomMppsTest $test, DiagnosticTestRecorder $recorder, RegistryAudit $audit): RedirectResponse
    {
        Gate::authorize('verify', $dicomNode);
        abort_unless($request->user()?->hasPermission('registry.manage'), 403);
        if ($dicomNode->archived_at !== null || ! $dicomNode->supports_mpps) {
            return back()->with('error', 'Der ausgewählte DICOM-Knoten unterstützt keinen ausführbaren MPPS-Test.');
        }

        $result = $test->run($dicomNode, strtoupper($request->validated('calling_ae_title')), strtoupper($request->validated('called_ae_title')));
        DB::transaction(function () use ($request, $dicomNode, $result, $recorder, $audit): void {
            $recorder->record($result, $dicomNode, $request->user());
            $audit->record('diagnostics.mpps.completed', $dicomNode, $request->user(), [
                'test_id' => $result->testId,
                'status' => $result->status->value,
                'authorized_test_confirmed' => true,
                'mpps_instance_uid' => $result->details['mppsInstanceUid'],
                'system_public_id' => $dicomNode->system->public_id,
            ]);
        });
        $response = back()->with('diagnosticResult', $result->toArray());

        return $result->status === DiagnosticTestStatus::Success ? $response->with('success', $result->summary) : $response->with('error', $result->summary);
    }
}
