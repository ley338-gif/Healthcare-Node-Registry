<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDicomNodeRequest;
use App\Http\Requests\UpdateDicomNodeRequest;
use App\Models\DicomNode;
use App\Models\System;
use App\Services\Dicom\DicomEchoService;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class DicomNodeController extends Controller
{
    public function store(
        StoreDicomNodeRequest $request,
        System $system,
        RegistryAudit $audit,
    ): RedirectResponse {
        $dicomNode = DB::transaction(
            function () use ($request, $system, $audit): DicomNode {
                $dicomNode = $system
                    ->dicomNodes()
                    ->create($request->validated());

                $audit->record(
                    'registry.dicom_node.created',
                    $dicomNode,
                    $request->user(),
                    [
                        'system_public_id' => $system->public_id,
                    ],
                );

                return $dicomNode;
            },
        );

        return back()->with(
            'success',
            "DICOM-Knoten {$dicomNode->name} wurde angelegt.",
        );
    }

    public function update(
        UpdateDicomNodeRequest $request,
        DicomNode $dicomNode,
        RegistryAudit $audit,
    ): RedirectResponse {
        DB::transaction(
            function () use ($request, $dicomNode, $audit): void {
                $validated = $request->validated();

                $before = $dicomNode->only(array_keys($validated));

                $dicomNode->update($validated);

                $audit->record(
                    'registry.dicom_node.updated',
                    $dicomNode,
                    $request->user(),
                    [
                        'system_public_id' => $dicomNode->system->public_id,
                        'before' => $before,
                        'after' => $dicomNode->only(array_keys($validated)),
                    ],
                );
            },
        );

        return back()->with(
            'success',
            'DICOM-Knoten wurde aktualisiert.',
        );
    }

    public function verify(
        Request $request,
        DicomNode $dicomNode,
        DicomEchoService $echoService,
        RegistryAudit $audit,
    ): RedirectResponse {
        Gate::authorize('verify', $dicomNode);

        if ($dicomNode->archived_at !== null) {
            return back()->with(
                'error',
                'Ein archivierter DICOM-Knoten kann nicht geprüft werden.',
            );
        }

        if (! $dicomNode->supports_echo) {
            return back()->with(
                'error',
                'Für diesen DICOM-Knoten ist C-ECHO nicht aktiviert.',
            );
        }

        $result = $echoService->test($dicomNode);

        DB::transaction(
            function () use (
                $request,
                $dicomNode,
                $result,
                $audit,
            ): void {
                $dicomNode->update([
                    'last_verified_at' => now(),
                    'last_verification_status' => $result->status,
                    'last_verification_duration_ms' => $result->durationMilliseconds,
                    'last_verification_message' => $result->message,
                ]);

                $audit->record(
                    'registry.dicom_node.verified',
                    $dicomNode,
                    $request->user(),
                    [
                        'successful' => $result->successful,
                        'status' => $result->status,
                        'duration_milliseconds' => $result->durationMilliseconds,
                        'exit_code' => $result->exitCode,
                        'system_public_id' => $dicomNode->system->public_id,
                    ],
                );
            },
        );

        if ($result->successful) {
            return back()->with(
                'success',
                sprintf(
                    'C-ECHO erfolgreich (%d ms).',
                    $result->durationMilliseconds,
                ),
            );
        }

        return back()->with(
            'error',
            sprintf(
                'C-ECHO fehlgeschlagen: %s',
                $result->message,
            ),
        );
    }

    public function archive(
        Request $request,
        DicomNode $dicomNode,
        RegistryAudit $audit,
    ): RedirectResponse {
        Gate::authorize('archive', $dicomNode);

        if ($dicomNode->archived_at !== null) {
            return back()->with(
                'error',
                'DICOM-Knoten ist bereits archiviert.',
            );
        }

        DB::transaction(
            function () use ($request, $dicomNode, $audit): void {
                $dicomNode->update([
                    'status' => 'inactive',
                    'archived_at' => now(),
                ]);

                $audit->record(
                    'registry.dicom_node.archived',
                    $dicomNode,
                    $request->user(),
                    [
                        'system_public_id' => $dicomNode->system->public_id,
                    ],
                );
            },
        );

        return back()->with(
            'success',
            'DICOM-Knoten wurde archiviert.',
        );
    }
}
