<?php

namespace App\Http\Controllers;

use App\Http\Requests\RunMoveTestRequest;
use App\Models\DicomConnection;
use App\Services\Diagnostics\DiagnosticTestRecorder;
use App\Services\Diagnostics\DiagnosticTestStatus;
use App\Services\Diagnostics\DicomMoveTest;
use App\Support\DiagnosticPermission;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class MoveDiagnosticController extends Controller
{
    public function __invoke(
        RunMoveTestRequest $request,
        DicomConnection $dicomConnection,
        DicomMoveTest $test,
        DiagnosticTestRecorder $recorder,
        RegistryAudit $audit,
    ): RedirectResponse {
        Gate::authorize('view', $dicomConnection);
        Gate::authorize(DiagnosticPermission::Move->value);

        if ($dicomConnection->archived_at !== null || ! $dicomConnection->test_enabled) {
            return back()->with('error', 'Diese DICOM-Verbindung ist nicht für Tests freigegeben.');
        }
        if ($dicomConnection->service !== DicomConnection::SERVICE_MOVE || $dicomConnection->destination_dicom_node_id === null) {
            return back()->with('error', 'Die Verbindung ist kein vollständig konfigurierter C-MOVE-Pfad.');
        }

        $dicomConnection->loadMissing(['targetNode.system', 'destinationNode']);
        $targetNode = $dicomConnection->targetNode;
        $destinationNode = $dicomConnection->destinationNode;
        if ($targetNode->archived_at !== null || $destinationNode === null || $destinationNode->archived_at !== null) {
            return back()->with('error', 'Ziel- oder Move-Destination-Knoten ist archiviert.');
        }

        $result = $test->run(
            $targetNode,
            $destinationNode,
            strtoupper($request->validated('calling_ae_title')),
            strtoupper($request->validated('called_ae_title')),
        );

        DB::transaction(function () use ($request, $dicomConnection, $targetNode, $destinationNode, $result, $recorder, $audit): void {
            $recorder->record($result, $targetNode, $request->user());
            $audit->record('diagnostics.move.completed', $dicomConnection, $request->user(), [
                'authorized_test_confirmed' => true,
                'test_id' => $result->testId,
                'status' => $result->status->value,
                'study_instance_uid' => $result->details['studyInstanceUid'],
                'target_node_public_id' => $targetNode->public_id,
                'destination_node_public_id' => $destinationNode->public_id,
                'destination_ae_title' => $destinationNode->ae_title,
            ]);
        });

        $response = back()->with('diagnosticResult', $result->toArray());

        return $result->status === DiagnosticTestStatus::Success
            ? $response->with('success', $result->summary)
            : $response->with('error', $result->summary);
    }
}
