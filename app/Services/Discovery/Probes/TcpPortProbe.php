<?php

namespace App\Services\Discovery\Probes;

interface TcpPortProbe
{
    /**
     * Prüft mehrere Host/Port-Kombinationen parallel per TCP-Connect.
     *
     * @param  array<string, list<int>>  $targets  IP-Adresse => Liste der zu prüfenden Ports
     * @return array<string, array<int, PortProbeResult>> IP-Adresse => Port => Ergebnis
     */
    public function scanMany(array $targets, int $timeoutSeconds): array;
}
