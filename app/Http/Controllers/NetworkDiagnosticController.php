<?php

namespace App\Http\Controllers;

use App\Models\DicomNode;
use App\Services\Diagnostics\DiagnosticTestStatus;
use App\Services\Diagnostics\NetworkConnectionTest;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class NetworkDiagnosticController extends Controller
{
    public function __invoke(
        Request $request,
        DicomNode $dicomNode,
        NetworkConnectionTest $networkTest,
        RegistryAudit $audit,
    ): RedirectResponse {
        Gate::authorize('verify', $dicomNode);

        if ($dicomNode->archived_at !== null) {
            return back()->with('error', 'Ein archivierter DICOM-Knoten kann nicht getestet werden.');
        }

        $result = $networkTest->run($dicomNode);

        $audit->record(
            'diagnostics.network.completed',
            $dicomNode,
            $request->user(),
            [
                'test_id' => $result->testId,
                'status' => $result->status->value,
                'duration_milliseconds' => $result->durationMilliseconds,
                'system_public_id' => $dicomNode->system->public_id,
            ],
        );

        $response = back()->with('diagnosticResult', $result->toArray());

        return $result->status === DiagnosticTestStatus::Success
            ? $response->with('success', $result->summary)
            : $response->with('error', $result->summary);
    }
}
