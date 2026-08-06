<?php

namespace App\Services\Discovery;

use App\Services\Discovery\Probes\NativeTcpPortProbe;
use App\Services\Discovery\Probes\TcpPortProbe;

/**
 * Phase B: TCP-Portprüfung gegen die für den Discovery-Lauf ausgewählte,
 * begrenzte Portliste. Reiner PHP-Socket-Scan (kein Nmap), siehe
 * NativeTcpPortProbe für die nicht-blockierende Umsetzung.
 */
final class PortScanService
{
    public function __construct(
        private readonly TcpPortProbe $probe = new NativeTcpPortProbe,
    ) {}

    /**
     * @param  array<string, list<int>>  $targets  IP => zu prüfende Ports
     * @return array<string, array<int, array{open: bool, response_time_ms: int|null}>>
     */
    public function scanMany(array $targets, int $timeoutSeconds): array
    {
        $scan = $this->probe->scanMany($targets, $timeoutSeconds);

        $results = [];
        foreach ($scan as $ip => $ports) {
            foreach ($ports as $port => $result) {
                $results[$ip][$port] = [
                    'open' => $result->open,
                    'response_time_ms' => $result->responseTimeMs,
                ];
            }
        }

        return $results;
    }
}
