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
    public function scanMany(array $targets, int $timeoutSeconds, int $retries = 0): array
    {
        $results = [];
        $pending = $targets;

        for ($attempt = 0; $attempt <= max(0, min(3, $retries)) && $pending !== []; $attempt++) {
            $nextPending = [];
            foreach ($this->probe->scanMany($pending, $timeoutSeconds) as $ip => $ports) {
                foreach ($ports as $port => $result) {
                    $results[$ip][$port] = [
                        'open' => $result->open,
                        'response_time_ms' => $result->responseTimeMs,
                    ];
                    if (! $result->open) {
                        $nextPending[$ip][] = $port;
                    }
                }
            }
            $pending = $nextPending;
        }

        return $results;
    }
}
