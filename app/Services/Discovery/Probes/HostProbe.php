<?php

namespace App\Services\Discovery\Probes;

interface HostProbe
{
    /**
     * Prüft mehrere Hosts parallel (z. B. per ICMP-Ping) und liefert für
     * jede IP-Adresse ein Ergebnis. Muss auch dann ein Ergebnis für jede
     * IP liefern, wenn der Mechanismus (z. B. ping-Binary) unavailable ist -
     * in diesem Fall reachable=false mit einem erklärenden error.
     *
     * @param  list<string>  $ips
     * @return array<string, HostProbeResult>
     */
    public function pingBatch(array $ips, int $timeoutSeconds): array;

    public function reverseDns(string $ip): ?string;
}
