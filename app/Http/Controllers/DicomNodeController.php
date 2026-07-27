<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDicomNodeRequest;
use App\Http\Requests\UpdateDicomNodeRequest;
use App\Models\DicomNode;
use App\Models\System;
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
                        'system_public_id' => $dicomNode
                            ->system
                            ->public_id,
                        'before' => $before,
                        'after' => $dicomNode->only(
                            array_keys($validated),
                        ),
                    ],
                );
            },
        );

        return back()->with(
            'success',
            'DICOM-Knoten wurde aktualisiert.',
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
                        'system_public_id' => $dicomNode
                            ->system
                            ->public_id,
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
