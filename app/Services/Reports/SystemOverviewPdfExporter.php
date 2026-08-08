<?php

namespace App\Services\Reports;

use Illuminate\Support\Collection;

final class SystemOverviewPdfExporter
{
    public function __construct(private readonly TabularPdfExporter $pdf) {}

    /** @param Collection<int, array<string, mixed>> $rows */
    public function export(Collection $rows): string
    {
        $tableRows = $rows->map(static fn (array $row): array => [
            implode(' / ', array_filter([$row['organization'], $row['site'], $row['department']])),
            (string) $row['system'],
            strtoupper((string) $row['system_type']).' / '.(string) $row['system_status'],
            implode(' / ', array_filter([$row['hostname'], $row['ip_address']])),
            implode(' / ', array_filter([$row['vendor'], $row['product'], $row['version']])),
            (string) ($row['dicom_node'] ?? '-'),
            $row['ae_title'] === null ? '-' : $row['ae_title'].' @ '.$row['dicom_host'].':'.$row['dicom_port'],
        ]);

        return $this->pdf->export('System- und Knotenuebersicht', 'Gefilterte Registry-Systeme', [
            ['label' => 'Kontext', 'width' => 135],
            ['label' => 'System', 'width' => 125],
            ['label' => 'Typ / Status', 'width' => 100],
            ['label' => 'Netzwerk', 'width' => 120],
            ['label' => 'Produkt', 'width' => 120],
            ['label' => 'DICOM-Knoten', 'width' => 90],
            ['label' => 'DICOM-Endpunkt', 'width' => 88],
        ], $tableRows);
    }
}
