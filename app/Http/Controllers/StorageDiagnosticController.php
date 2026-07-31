<?php

namespace App\Http\Controllers;

use App\Http\Requests\RunStorageTestRequest;
use App\Models\DicomNode;
use App\Services\Diagnostics\DiagnosticTestRecorder;
use App\Services\Diagnostics\DiagnosticTestStatus;
use App\Services\Diagnostics\DicomStorageTest;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class StorageDiagnosticController extends Controller
{
    public function __invoke(RunStorageTestRequest $request, DicomNode $dicomNode, DicomStorageTest $test, DiagnosticTestRecorder $recorder, RegistryAudit $audit): RedirectResponse
    {
        Gate::authorize('verify', $dicomNode);
        abort_unless($request->user()?->hasPermission('tests.run.storage'), 403);
        if ($dicomNode->archived_at !== null) {
            return back()->with('error', 'Ein archivierter DICOM-Knoten kann nicht getestet werden.');
        }
        if (! $dicomNode->supports_store) {
            return back()->with('error', 'Für diesen DICOM-Knoten ist Storage nicht aktiviert.');
        }

        $result = $test->run($dicomNode, strtoupper($request->validated('calling_ae_title')), strtoupper($request->validated('called_ae_title')));
        DB::transaction(function () use ($request, $dicomNode, $result, $recorder, $audit): void {
            $recorder->record($result, $dicomNode, $request->user());
            $audit->record('diagnostics.storage.completed', $dicomNode, $request->user(), [
                'test_id' => $result->testId, 'status' => $result->status->value,
                'sop_class_uid' => '1.2.840.10008.5.1.4.1.1.7',
                'sop_instance_uid' => $result->details['sopInstanceUid'],
                'system_public_id' => $dicomNode->system->public_id,
            ]);
        });
        $response = back()->with('diagnosticResult', $result->toArray());

        return $result->status === DiagnosticTestStatus::Success ? $response->with('success', $result->summary) : $response->with('error', $result->summary);
    }
}
