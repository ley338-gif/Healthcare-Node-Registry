<?php

namespace App\Services\Reports;

use Illuminate\Support\Collection;

final class FirewallMatrixPdfExporter
{
    public function __construct(private readonly TabularPdfExporter $pdf) {}

    /** @param Collection<int, array<string, mixed>> $rows */
    public function export(Collection $rows): string
    {
        $tableRows = $rows->map(static fn (array $row): array => [
            implode(' / ', array_filter([$row['source_organization'], $row['source_site'], $row['source_department']])).' > '.implode(' / ', array_filter([$row['target_organization'], $row['target_site'], $row['target_department']])),
            $row['source_system'].' - '.$row['source_host'],
            $row['target_system'].' - '.$row['target_host'],
            (string) $row['port'],
            strtoupper((string) $row['service']),
            $row['tls_enabled'] ? 'Ja' : 'Nein',
            $row['source_ae_title'].' > '.$row['target_ae_title'],
        ]);

        return $this->pdf->export('Firewall- und Portmatrix', 'Aktive DICOM-Verbindungen', [
            ['label' => 'Kontext', 'width' => 125],
            ['label' => 'Quelle', 'width' => 180],
            ['label' => 'Ziel', 'width' => 180],
            ['label' => 'Port', 'width' => 55],
            ['label' => 'Dienst', 'width' => 90],
            ['label' => 'TLS', 'width' => 45],
            ['label' => 'AE Titles', 'width' => 103],
        ], $tableRows);
    }
}
