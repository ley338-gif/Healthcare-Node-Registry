<?php

namespace App\Http\Controllers;

use App\Http\Requests\RunStorageCommitmentTestRequest;
use App\Models\DicomNode;
use App\Services\Diagnostics\DiagnosticTestRecorder;
use App\Services\Diagnostics\DiagnosticTestStatus;
use App\Services\Diagnostics\DicomStorageCommitmentTest;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class StorageCommitmentDiagnosticController extends Controller
{
    public function __invoke(RunStorageCommitmentTestRequest $request, DicomNode $dicomNode, DicomStorageCommitmentTest $test, DiagnosticTestRecorder $recorder, RegistryAudit $audit): RedirectResponse
    {
        Gate::authorize('verify', $dicomNode);
        abort_unless($request->user()?->hasPermission('tests.run.storage'), 403);
        if ($dicomNode->archived_at !== null || ! $dicomNode->supports_storage_commitment) {
            return back()->with('error', 'Der ausgewählte DICOM-Knoten unterstützt keinen ausführbaren Storage-Commitment-Test.');
        }

        $lock = Cache::lock('diagnostics:storage-commitment', 90);
        if (! $lock->get()) {
            return back()->with('error', 'Ein Storage-Commitment-Test läuft bereits. Bitte versuchen Sie es später erneut.');
        }

        try {
            $result = $test->run($dicomNode, strtoupper($request->validated('calling_ae_title')), strtoupper($request->validated('called_ae_title')));
        } finally {
            $lock->release();
        }

        DB::transaction(function () use ($request, $dicomNode, $result, $recorder, $audit): void {
            $recorder->record($result, $dicomNode, $request->user());
            $audit->record('diagnostics.storage_commitment.completed', $dicomNode, $request->user(), [
                'test_id' => $result->testId,
                'status' => $result->status->value,
                'authorized_test_confirmed' => true,
                'sop_instance_uid' => $result->details['sopInstanceUid'],
                'transaction_uid' => $result->details['transactionUid'],
                'system_public_id' => $dicomNode->system->public_id,
            ]);
        });
        $response = back()->with('diagnosticResult', $result->toArray());

        return $result->status === DiagnosticTestStatus::Success ? $response->with('success', $result->summary) : $response->with('error', $result->summary);
    }
}
