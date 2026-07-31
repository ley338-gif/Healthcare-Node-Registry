<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveDiagnosticTestProfileRequest;
use App\Models\DiagnosticTestProfile;
use App\Models\DicomNode;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class DiagnosticTestProfileController extends Controller
{
    public function store(SaveDiagnosticTestProfileRequest $request, RegistryAudit $audit): RedirectResponse
    {
        Gate::authorize('create', DiagnosticTestProfile::class);
        $profile = DB::transaction(function () use ($request, $audit): DiagnosticTestProfile {
            $profile = DiagnosticTestProfile::query()->create($this->attributes($request) + ['created_by' => $request->user()?->id]);
            $audit->record('diagnostics.profile.created', $profile, $request->user());

            return $profile;
        });

        return back()->with('success', "Testprofil {$profile->name} wurde angelegt.");
    }

    public function update(SaveDiagnosticTestProfileRequest $request, DiagnosticTestProfile $profile, RegistryAudit $audit): RedirectResponse
    {
        Gate::authorize('update', $profile);
        DB::transaction(function () use ($request, $profile, $audit): void {
            $profile->update($this->attributes($request));
            $audit->record('diagnostics.profile.updated', $profile, $request->user());
        });

        return back()->with('success', 'Testprofil wurde aktualisiert.');
    }

    public function archive(Request $request, DiagnosticTestProfile $profile, RegistryAudit $audit): RedirectResponse
    {
        Gate::authorize('archive', $profile);
        if ($profile->archived_at !== null) {
            return back()->with('error', 'Testprofil ist bereits archiviert.');
        }
        DB::transaction(function () use ($request, $profile, $audit): void {
            $profile->update(['enabled' => false, 'archived_at' => now()]);
            $audit->record('diagnostics.profile.archived', $profile, $request->user());
        });

        return back()->with('success', 'Testprofil wurde archiviert.');
    }

    /** @return array<string, mixed> */
    private function attributes(SaveDiagnosticTestProfileRequest $request): array
    {
        $validated = $request->validated();
        $node = DicomNode::query()->where('public_id', $validated['dicom_node_public_id'])->whereNull('archived_at')->firstOrFail();

        return [
            'name' => $validated['name'], 'description' => $validated['description'] ?? null,
            'test_type' => $validated['test_type'], 'dicom_node_id' => $node->id,
            'calling_ae_title' => isset($validated['calling_ae_title']) ? strtoupper($validated['calling_ae_title']) : null,
            'configuration' => $validated['configuration'], 'timeout_seconds' => $validated['timeout_seconds'],
            'enabled' => $validated['enabled'],
        ];
    }
}
