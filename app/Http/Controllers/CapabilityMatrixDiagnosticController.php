<?php

namespace App\Http\Controllers;

use App\Http\Requests\RunCapabilityMatrixRequest;
use App\Models\DicomNode;
use App\Services\Diagnostics\DiagnosticTestRecorder;
use App\Services\Diagnostics\DiagnosticTestStatus;
use App\Services\Diagnostics\DicomCapabilityMatrixTest;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class CapabilityMatrixDiagnosticController extends Controller
{
    public function __invoke(RunCapabilityMatrixRequest $request, DicomNode $dicomNode, DicomCapabilityMatrixTest $test, DiagnosticTestRecorder $recorder, RegistryAudit $audit): RedirectResponse
    {
        Gate::authorize('verify', $dicomNode);
        abort_unless($request->user()?->hasPermission('tests.run.storage'), 403);
        abort_if($dicomNode->archived_at !== null || ! $dicomNode->supports_store, 422);
        $result = $test->run($dicomNode, strtoupper($request->validated('calling_ae_title')), strtoupper($request->validated('called_ae_title')));
        DB::transaction(function () use ($request, $dicomNode, $result, $recorder, $audit): void {
            $recorder->record($result, $dicomNode, $request->user());
            $audit->record('diagnostics.capability-matrix.completed', $dicomNode, $request->user(), ['test_id' => $result->testId, 'status' => $result->status->value, 'verification_mode' => 'presentation_context']);
        });
        $response = back()->with('diagnosticResult', $result->toArray());

        return $result->status === DiagnosticTestStatus::Success ? $response->with('success', $result->summary) : $response->with('error', $result->summary);
    }
}
