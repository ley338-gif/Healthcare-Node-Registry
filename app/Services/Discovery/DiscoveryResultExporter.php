<?php

namespace App\Services\Discovery;

use App\Models\DiscoveredHost;
use App\Models\DiscoveryRun;
use Illuminate\Support\LazyCollection;

final class DiscoveryResultExporter
{
    /**
     * @param  LazyCollection<int, DiscoveredHost>  $hosts
     * @param  resource  $stream
     */
    public function writeCsv($stream, LazyCollection $hosts): void
    {
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, [
            'Fund-ID',
            'IP-Adresse',
            'Hostname',
            'Erreichbar',
            'Ping (ms)',
            'Review-Status',
            'Confidence-Stufe',
            'Confidence (%)',
            'Vorgeschlagener Systemtyp',
            'Registry-System-ID',
            'Registry-System',
            'Offene Ports',
            'Offene DICOM-Kandidatenports',
            'C-ECHO erfolgreich',
            'Erfolgreiche C-ECHO-Endpunkte',
            'Zuletzt gesehen',
        ], ';', '"', '');

        foreach ($hosts as $host) {
            fputcsv($stream, array_map($this->csvCell(...), array_values($this->row($host))), ';', '"', '');
        }
    }

    /**
     * @param  LazyCollection<int, DiscoveredHost>  $hosts
     * @param  array<string, mixed>  $filters
     * @param  resource  $stream
     */
    public function writeJson($stream, DiscoveryRun $run, array $filters, LazyCollection $hosts): void
    {
        fwrite($stream, '{"run":');
        fwrite($stream, $this->json([
            'public_id' => $run->public_id,
            'name' => $run->name,
            'ip_range' => $run->ip_range,
            'status' => $run->status,
            'started_at' => $run->started_at?->toIso8601String(),
            'finished_at' => $run->finished_at?->toIso8601String(),
        ]));
        fwrite($stream, ',"exported_at":'.$this->json(now()->toIso8601String()));
        fwrite($stream, ',"filters":'.$this->json(array_filter($filters, filled(...))));
        fwrite($stream, ',"results":[');

        $first = true;
        foreach ($hosts as $host) {
            if (! $first) {
                fwrite($stream, ',');
            }
            fwrite($stream, $this->json($this->row($host)));
            $first = false;
        }

        fwrite($stream, ']}');
    }

    /** @return array<string, mixed> */
    private function row(DiscoveredHost $host): array
    {
        $openPorts = $host->ports->where('is_open', true)->pluck('port')->sort()->values();
        $dicomPorts = $host->ports->where('is_open', true)->where('is_dicom_candidate', true)->pluck('port')->sort()->values();
        $successfulEchoes = $host->dicomResults->where('echo_successful', true);

        return [
            'public_id' => $host->public_id,
            'ip_address' => $host->ip_address,
            'hostname' => $host->hostname,
            'is_reachable' => $host->is_reachable,
            'ping_latency_ms' => $host->ping_latency_ms,
            'review_status' => $host->status,
            'confidence_level' => $host->confidence_score,
            'confidence_percentage' => $host->confidence_percentage,
            'suggested_system_type' => $host->suggested_system_type,
            'registry_system_public_id' => $host->system?->public_id,
            'registry_system_name' => $host->system?->name,
            'open_ports' => $openPorts->all(),
            'open_dicom_candidate_ports' => $dicomPorts->all(),
            'successful_c_echo' => $successfulEchoes->isNotEmpty(),
            'successful_c_echo_endpoints' => $successfulEchoes
                ->map(fn ($result) => "{$result->called_ae}@{$host->ip_address}:{$result->port}")
                ->values()
                ->all(),
            'last_seen_at' => $host->last_seen_at?->toIso8601String(),
        ];
    }

    private function csvCell(mixed $value): string
    {
        $cell = match (true) {
            is_bool($value) => $value ? 'ja' : 'nein',
            $value === null => '',
            is_array($value) => implode(',', array_map(strval(...), $value)),
            default => (string) $value,
        };

        return preg_match('/^[=+\-@]/', $cell) === 1 ? "'{$cell}" : $cell;
    }

    private function json(mixed $value): string
    {
        return (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
