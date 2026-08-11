<?php

namespace App\Services\Reports;

use App\Models\DiscoveredHost;
use App\Models\DiscoveryRun;
use Illuminate\Support\Collection;

final class DiscoveryResultPdfExporter
{
    public function __construct(private readonly TabularPdfExporter $pdf) {}

    /**
     * @param  Collection<int, DiscoveredHost>  $hosts
     * @param  array<string, mixed>  $filters
     */
    public function export(DiscoveryRun $run, array $filters, Collection $hosts): string
    {
        $rows = $hosts->map(static function (DiscoveredHost $host): array {
            $openPorts = $host->ports->where('is_open', true)->pluck('port')->sort()->implode(', ');
            $echoEndpoints = $host->dicomResults
                ->where('echo_successful', true)
                ->map(fn ($result): string => "{$result->called_ae}@{$host->ip_address}:{$result->port}")
                ->implode(', ');

            return [
                $host->ip_address,
                $host->hostname ?? '-',
                $host->is_reachable ? 'Ja' : 'Nein',
                $host->status,
                $host->confidence_score.' / '.$host->confidence_percentage.' %',
                $host->suggested_system_type ?? '-',
                $openPorts === '' ? '-' : $openPorts,
                $echoEndpoints === '' ? '-' : $echoEndpoints,
            ];
        });

        $activeFilterCount = count(array_filter($filters, filled(...)));
        $subtitle = $run->name.' | '.$run->ip_range.' | '.$activeFilterCount.' aktive Filter';

        return $this->pdf->export('Discovery-Ergebnisbericht', $subtitle, [
            ['label' => 'IP-Adresse', 'width' => 90],
            ['label' => 'Hostname', 'width' => 150],
            ['label' => 'Erreichbar', 'width' => 60],
            ['label' => 'Review', 'width' => 80],
            ['label' => 'Confidence', 'width' => 95],
            ['label' => 'Systemtyp', 'width' => 90],
            ['label' => 'Offene Ports', 'width' => 95],
            ['label' => 'C-ECHO-Endpunkte', 'width' => 120],
        ], $rows);
    }
}
