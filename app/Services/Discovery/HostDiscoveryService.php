<?php

namespace App\Services\Discovery;

use App\Services\Discovery\Probes\HostProbe;
use App\Services\Discovery\Probes\NativeHostProbe;

/**
 * Phase A: Host-Erkennung (Ping/alternative Erreichbarkeitsprüfung +
 * Reverse-DNS). Liefert für jede angefragte IP ein Ergebnis, unabhängig
 * davon, ob ICMP verfügbar ist - die endgültige "erreichbar"-Aussage
 * kombiniert die aufrufende Stelle (RunDiscoveryScanJob) ggf. noch mit
 * dem Portscan-Ergebnis, wenn "auch nicht antwortende Hosts prüfen"
 * aktiviert ist.
 */
final class HostDiscoveryService
{
    public function __construct(
        private readonly HostProbe $probe = new NativeHostProbe,
    ) {}

    /**
     * @param  list<string>  $ips
     * @return array<string, array{reachable: bool, latency_ms: int|null, error: string|null}>
     */
    public function pingBatch(array $ips, int $timeoutSeconds): array
    {
        $results = [];
        foreach ($this->probe->pingBatch($ips, $timeoutSeconds) as $ip => $result) {
            $results[$ip] = [
                'reachable' => $result->reachable,
                'latency_ms' => $result->latencyMs,
                'error' => $result->error,
            ];
        }

        return $results;
    }

    public function reverseDns(string $ip): ?string
    {
        return $this->probe->reverseDns($ip);
    }
}
