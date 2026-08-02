<?php

namespace App\Http\Controllers;

use App\Http\Requests\RunGetTestRequest;
use App\Models\DicomConnection;
use App\Services\Diagnostics\DiagnosticTestRecorder;
use App\Services\Diagnostics\DiagnosticTestStatus;
use App\Services\Diagnostics\DicomGetTest;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class GetDiagnosticController extends Controller
{
    public function __invoke(RunGetTestRequest $request, DicomConnection $dicomConnection, DicomGetTest $test, DiagnosticTestRecorder $recorder, RegistryAudit $audit): RedirectResponse
    {
        Gate::authorize('view', $dicomConnection);
        abort_unless($request->user()?->hasPermission('registry.manage'), 403);
        if ($dicomConnection->archived_at !== null || ! $dicomConnection->test_enabled) {
            return back()->with('error', 'Diese DICOM-Verbindung ist nicht für Tests freigegeben.');
        }
        if ($dicomConnection->service !== DicomConnection::SERVICE_GET) {
            return back()->with('error', 'Die Verbindung ist kein C-GET-Pfad.');
        }

        $dicomConnection->loadMissing('targetNode.system');
        $targetNode = $dicomConnection->targetNode;
        if ($targetNode->archived_at !== null) {
            return back()->with('error', 'Der Zielknoten ist archiviert.');
        }

        $result = $test->run($targetNode, strtoupper($request->validated('calling_ae_title')), strtoupper($request->validated('called_ae_title')));
        DB::transaction(function () use ($request, $dicomConnection, $targetNode, $result, $recorder, $audit): void {
            $recorder->record($result, $targetNode, $request->user());
            $audit->record('diagnostics.get.completed', $dicomConnection, $request->user(), [
                'authorized_test_confirmed' => true, 'test_id' => $result->testId,
                'status' => $result->status->value, 'study_instance_uid' => $result->details['studyInstanceUid'],
                'target_node_public_id' => $targetNode->public_id,
                'received_object_count' => $result->details['receivedObjectCount'],
            ]);
        });
        $response = back()->with('diagnosticResult', $result->toArray());

        return $result->status === DiagnosticTestStatus::Success ? $response->with('success', $result->summary) : $response->with('error', $result->summary);
    }
}
