<?php

namespace App\Services\Discovery;

use App\Models\DiscoveredHost;
use App\Models\DiscoveryRun;
use Illuminate\Support\Collection;

final class DiscoveryRunComparisonService
{
    /**
     * @return array{summary: array{new: int, missing: int, changed: int, unchanged: int, total: int}, results: list<array<string, mixed>>}
     */
    public function compare(DiscoveryRun $baseline, DiscoveryRun $current): array
    {
        $baselineHosts = $this->hostsByIp($baseline);
        $currentHosts = $this->hostsByIp($current);
        $addresses = $baselineHosts->keys()->merge($currentHosts->keys())->unique()->sort()->values();

        $results = $addresses->map(function (string $address) use ($baselineHosts, $currentHosts): array {
            $baselineHost = $baselineHosts->get($address);
            $currentHost = $currentHosts->get($address);

            if (! $baselineHost instanceof DiscoveredHost) {
                return $this->result('new', null, $currentHost);
            }

            if (! $currentHost instanceof DiscoveredHost) {
                return $this->result('missing', $baselineHost, null);
            }

            $baselineSnapshot = $this->snapshot($baselineHost);
            $currentSnapshot = $this->snapshot($currentHost);
            $changes = $this->changes($baselineSnapshot, $currentSnapshot);

            return [
                'change_type' => $changes === [] ? 'unchanged' : 'changed',
                'ip_address' => $address,
                'baseline' => $baselineSnapshot,
                'current' => $currentSnapshot,
                'changes' => $changes,
            ];
        })->values()->all();

        $summary = ['new' => 0, 'missing' => 0, 'changed' => 0, 'unchanged' => 0, 'total' => count($results)];
        foreach ($results as $result) {
            $summary[$result['change_type']]++;
        }

        return ['summary' => $summary, 'results' => $results];
    }

    /** @return Collection<string, DiscoveredHost> */
    private function hostsByIp(DiscoveryRun $run): Collection
    {
        return $run->hosts()
            ->with(['ports', 'dicomResults'])
            ->get()
            ->keyBy('ip_address');
    }

    /** @return array<string, mixed> */
    private function snapshot(DiscoveredHost $host): array
    {
        $openPorts = $host->ports
            ->where('is_open', true)
            ->pluck('port')
            ->map(fn (mixed $port): int => (int) $port)
            ->unique()
            ->sort()
            ->values()
            ->all();
        $dicomEndpoints = $host->dicomResults
            ->where('echo_successful', true)
            ->map(fn ($result): string => $result->called_ae.'@'.$host->ip_address.':'.$result->port)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return [
            'public_id' => $host->public_id,
            'ip_address' => $host->ip_address,
            'hostname' => $host->hostname,
            'reverse_dns' => $host->reverse_dns,
            'is_reachable' => $host->is_reachable,
            'open_ports' => $openPorts,
            'dicom_endpoints' => $dicomEndpoints,
            'suggested_system_type' => $host->suggested_system_type,
            'confidence_score' => $host->confidence_score,
            'confidence_percentage' => $host->confidence_percentage,
        ];
    }

    /**
     * @param  array<string, mixed>  $baseline
     * @param  array<string, mixed>  $current
     * @return list<array{field: string, before: mixed, after: mixed}>
     */
    private function changes(array $baseline, array $current): array
    {
        $fields = [
            'hostname',
            'reverse_dns',
            'is_reachable',
            'open_ports',
            'dicom_endpoints',
            'suggested_system_type',
            'confidence_score',
            'confidence_percentage',
        ];
        $changes = [];

        foreach ($fields as $field) {
            if ($baseline[$field] !== $current[$field]) {
                $changes[] = ['field' => $field, 'before' => $baseline[$field], 'after' => $current[$field]];
            }
        }

        return $changes;
    }

    /** @return array<string, mixed> */
    private function result(string $changeType, ?DiscoveredHost $baseline, ?DiscoveredHost $current): array
    {
        return [
            'change_type' => $changeType,
            'ip_address' => ($current ?? $baseline)?->ip_address,
            'baseline' => $baseline ? $this->snapshot($baseline) : null,
            'current' => $current ? $this->snapshot($current) : null,
            'changes' => [],
        ];
    }
}
