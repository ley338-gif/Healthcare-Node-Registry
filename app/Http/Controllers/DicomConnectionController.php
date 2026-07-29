<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDicomConnectionRequest;
use App\Http\Requests\UpdateDicomConnectionRequest;
use App\Models\DicomConnection;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class DicomConnectionController extends Controller
{
    public function store(
        StoreDicomConnectionRequest $request,
        RegistryAudit $audit,
    ): RedirectResponse {
        $connection = DB::transaction(
            function () use ($request, $audit): DicomConnection {
                $connection = DicomConnection::query()->create(
                    $request->validated(),
                );

                $audit->record(
                    'registry.dicom_connection.created',
                    $connection,
                    $request->user(),
                    [
                        'source_dicom_node_id' => $connection->source_dicom_node_id,
                        'target_dicom_node_id' => $connection->target_dicom_node_id,
                        'destination_dicom_node_id' => $connection->destination_dicom_node_id,
                        'service' => $connection->service,
                    ],
                );

                return $connection;
            },
        );

        return back()->with(
            'success',
            "DICOM-Verbindung {$connection->name} wurde angelegt.",
        );
    }

    public function update(
        UpdateDicomConnectionRequest $request,
        DicomConnection $dicomConnection,
        RegistryAudit $audit,
    ): RedirectResponse {
        DB::transaction(
            function () use (
                $request,
                $dicomConnection,
                $audit,
            ): void {
                $validated = $request->validated();

                $before = $dicomConnection->only(
                    array_keys($validated),
                );

                $dicomConnection->update($validated);

                $audit->record(
                    'registry.dicom_connection.updated',
                    $dicomConnection,
                    $request->user(),
                    [
                        'before' => $before,
                        'after' => $dicomConnection->only(
                            array_keys($validated),
                        ),
                    ],
                );
            },
        );

        return back()->with(
            'success',
            'DICOM-Verbindung wurde aktualisiert.',
        );
    }

    public function archive(
        Request $request,
        DicomConnection $dicomConnection,
        RegistryAudit $audit,
    ): RedirectResponse {
        Gate::authorize('archive', $dicomConnection);

        if ($dicomConnection->archived_at !== null) {
            return back()->with(
                'error',
                'DICOM-Verbindung ist bereits archiviert.',
            );
        }

        DB::transaction(
            function () use (
                $request,
                $dicomConnection,
                $audit,
            ): void {
                $dicomConnection->update([
                    'status' => 'inactive',
                    'test_enabled' => false,
                    'archived_at' => now(),
                ]);

                $audit->record(
                    'registry.dicom_connection.archived',
                    $dicomConnection,
                    $request->user(),
                    [
                        'source_dicom_node_id' => $dicomConnection->source_dicom_node_id,
                        'target_dicom_node_id' => $dicomConnection->target_dicom_node_id,
                        'service' => $dicomConnection->service,
                    ],
                );
            },
        );

        return back()->with(
            'success',
            'DICOM-Verbindung wurde archiviert.',
        );
    }
}
