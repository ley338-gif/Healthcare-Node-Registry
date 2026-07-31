<?php

namespace App\Http\Controllers;

use App\Models\DiagnosticTestProfile;
use App\Services\Diagnostics\DiagnosticTestRecorder;
use App\Services\Diagnostics\DiagnosticTestResult;
use App\Services\Diagnostics\DiagnosticTestStatus;
use App\Services\Diagnostics\NetworkConnectionTest;
use App\Services\Diagnostics\PacsQueryParameters;
use App\Services\Diagnostics\PacsQueryTest;
use App\Services\Diagnostics\WorklistFindParameters;
use App\Services\Diagnostics\WorklistFindTest;
use App\Services\Dicom\DicomEchoService;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class DiagnosticProfileExecutionController extends Controller
{
    public function __invoke(
        Request $request,
        DiagnosticTestProfile $profile,
        NetworkConnectionTest $network,
        DicomEchoService $echo,
        WorklistFindTest $worklist,
        PacsQueryTest $pacsQuery,
        DiagnosticTestRecorder $recorder,
        RegistryAudit $audit,
    ): RedirectResponse {
        Gate::authorize('execute', $profile);
        $node = $profile->dicomNode;

        if (! $profile->enabled || $profile->archived_at !== null || $node->archived_at !== null) {
            return back()->with('error', 'Dieses Testprofil kann nicht ausgeführt werden.');
        }

        $result = match ($profile->test_type) {
            'network' => $network->run($node),
            'dicom_echo' => $echo->test($node)->diagnosticResult,
            'worklist' => $worklist->run($node, $this->worklistParameters($profile)),
            'pacs_query' => $pacsQuery->run($node, $this->pacsParameters($profile)),
            default => null,
        };

        if (! $result instanceof DiagnosticTestResult) {
            return back()->with('error', 'Der Testtyp des Profils wird nicht unterstützt.');
        }

        DB::transaction(function () use ($request, $profile, $node, $result, $recorder, $audit): void {
            $recorder->record($result, $node, $request->user());
            $audit->record('diagnostics.profile.executed', $profile, $request->user(), [
                'test_id' => $result->testId,
                'test_type' => $result->testType,
                'status' => $result->status->value,
                'dicom_node_public_id' => $node->public_id,
            ]);
        });

        $response = back()->with('diagnosticResult', $result->toArray());

        return $result->status === DiagnosticTestStatus::Success
            ? $response->with('success', $result->summary)
            : $response->with('error', $result->summary);
    }

    private function worklistParameters(DiagnosticTestProfile $profile): WorklistFindParameters
    {
        $configuration = $profile->configuration;

        return new WorklistFindParameters(
            $profile->calling_ae_title ?? 'NODE_REGISTRY',
            $this->value($configuration, 'called_ae_title') ?? $profile->dicomNode->ae_title,
            $this->value($configuration, 'scheduled_station_ae_title'),
            $this->value($configuration, 'examination_date') ?? now()->format('Y-m-d'),
            $this->value($configuration, 'examination_date_to'),
            $this->value($configuration, 'modality'),
            $this->value($configuration, 'patient_name'),
            $this->value($configuration, 'patient_id'),
            $this->value($configuration, 'accession_number'),
        );
    }

    private function pacsParameters(DiagnosticTestProfile $profile): PacsQueryParameters
    {
        $configuration = $profile->configuration;

        return new PacsQueryParameters(
            $profile->calling_ae_title ?? 'NODE_REGISTRY',
            $this->value($configuration, 'called_ae_title') ?? $profile->dicomNode->ae_title,
            $this->value($configuration, 'patient_name'),
            $this->value($configuration, 'patient_id'),
            $this->value($configuration, 'accession_number'),
            $this->value($configuration, 'study_instance_uid'),
            $this->value($configuration, 'modality'),
            $this->value($configuration, 'study_date'),
            $this->value($configuration, 'study_date_to'),
            $this->value($configuration, 'study_description'),
        );
    }

    /** @param array<string, mixed> $configuration */
    private function value(array $configuration, string $key): ?string
    {
        $value = $configuration[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
