<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DicomConnection;
use App\Models\Organization;
use App\Models\Site;
use App\Models\System;
use App\Services\Reports\FirewallMatrixPdfExporter;
use App\Services\Reports\FirewallMatrixQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class FirewallMatrixReportController extends Controller
{
    public function index(Request $request, FirewallMatrixQuery $query): InertiaResponse
    {
        Gate::authorize('viewAny', DicomConnection::class);
        $filters = $this->filters($request);

        return Inertia::render('Reports/FirewallMatrix', [
            'rows' => $query->rows($filters), 'filters' => $filters,
            'organizations' => Organization::query()->active()->orderBy('name')->get(['public_id', 'name']),
            'sites' => Site::query()->active()->orderBy('name')->get(['public_id', 'organization_id', 'name']),
            'departments' => Department::query()->active()->orderBy('name')->get(['public_id', 'site_id', 'name']),
            'systems' => System::query()->active()->orderBy('name')->get(['public_id', 'organization_id', 'site_id', 'department_id', 'name']),
            'services' => DicomConnection::SERVICES,
        ]);
    }

    public function export(Request $request, string $format, FirewallMatrixQuery $query, FirewallMatrixPdfExporter $pdf): Response|StreamedResponse
    {
        Gate::authorize('viewAny', DicomConnection::class);
        abort_unless(in_array($format, ['csv', 'pdf'], true), 404);
        $rows = $query->rows($this->filters($request));
        $filename = 'firewall-portmatrix-'.now()->format('Y-m-d');
        if ($format === 'pdf') {
            return response($pdf->export($rows), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => "attachment; filename={$filename}.pdf"]);
        }

        return response()->streamDownload(function () use ($rows): void {
            $stream = fopen('php://output', 'wb');
            if ($stream === false) {
                return;
            }
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Quellorganisation', 'Quellstandort', 'Quellabteilung', 'Quellsystem', 'Quellhost', 'Calling AE', 'Zielorganisation', 'Zielstandort', 'Zielabteilung', 'Zielsystem', 'Zielhost', 'Called AE', 'Port', 'Dienst', 'TLS'], escape: '');
            foreach ($rows as $row) {
                fputcsv($stream, [$row['source_organization'], $row['source_site'], $row['source_department'], $row['source_system'], $row['source_host'], $row['source_ae_title'], $row['target_organization'], $row['target_site'], $row['target_department'], $row['target_system'], $row['target_host'], $row['target_ae_title'], $row['port'], $row['service'], $row['tls_enabled'] ? 'ja' : 'nein'], escape: '');
            }
            fclose($stream);
        }, "{$filename}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array<string,mixed> */
    private function filters(Request $request): array
    {
        return $request->validate([
            'organization' => ['nullable', 'uuid'], 'site' => ['nullable', 'uuid'], 'department' => ['nullable', 'uuid'],
            'system' => ['nullable', 'uuid'], 'service' => ['nullable', Rule::in(DicomConnection::SERVICES)],
        ]);
    }
}
