<?php

namespace App\Http\Controllers;

use App\Models\System;
use App\Services\Reports\SystemOverviewPdfExporter;
use App\Services\Reports\SystemOverviewQuery;
use App\Services\Reports\TabularXlsxExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class SystemOverviewExportController extends Controller
{
    /** @var list<string> */
    private const HEADERS = [
        'Organisation', 'Standort', 'Abteilung', 'System', 'Systemtyp', 'Systemstatus',
        'Hostname', 'FQDN', 'IP-Adresse', 'Hersteller', 'Produkt', 'Modell', 'Version',
        'Betriebssystem', 'Betriebssystemversion', 'Inventarnummer', 'Seriennummer',
        'DICOM-Knoten', 'AE Title', 'Modalität', 'DICOM-Host', 'DICOM-Port', 'DICOM-Rolle',
        'DICOM-Status', 'DICOM-TLS',
    ];

    public function __invoke(
        Request $request,
        string $format,
        SystemOverviewQuery $query,
        SystemOverviewPdfExporter $pdf,
        TabularXlsxExporter $xlsx,
    ): Response {
        Gate::authorize('viewAny', System::class);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:160'],
            'type' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'string', 'max:40'],
            'organization' => ['nullable', 'integer', 'exists:organizations,id'],
            'site' => ['nullable', 'integer', 'exists:sites,id'],
            'department' => ['nullable', 'integer', 'exists:departments,id'],
        ]);
        $rows = $query->rows($filters);
        $filename = 'system-knotenuebersicht-'.now()->format('Y-m-d');

        if ($format === 'pdf') {
            return response($pdf->export($rows), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename={$filename}.pdf",
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        abort_unless($format === 'xlsx', 404);
        $values = $rows->map(static fn (array $row): array => [
            $row['organization'], $row['site'], $row['department'], $row['system'], $row['system_type'], $row['system_status'],
            $row['hostname'], $row['fqdn'], $row['ip_address'], $row['vendor'], $row['product'], $row['model'], $row['version'],
            $row['operating_system'], $row['operating_system_version'], $row['inventory_number'], $row['serial_number'],
            $row['dicom_node'], $row['ae_title'], $row['modality'], $row['dicom_host'], $row['dicom_port'], $row['dicom_role'],
            $row['dicom_status'], $row['dicom_tls'],
        ]);

        return response($xlsx->export('Systeme und DICOM-Knoten', self::HEADERS, $values), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename={$filename}.xlsx",
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
