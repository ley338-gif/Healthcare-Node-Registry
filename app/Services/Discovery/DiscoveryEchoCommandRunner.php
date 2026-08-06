<?php

namespace App\Services\Discovery;

interface DiscoveryEchoCommandRunner
{
    /**
     * Führt mehrere C-ECHO-Versuche parallel aus (begrenzt durch die
     * Anzahl der übergebenen Targets - die Begrenzung selbst obliegt dem
     * Aufrufer, siehe config('discovery.max_ae_attempts_per_port')).
     *
     * @param  list<DiscoveryEchoTarget>  $targets
     * @return array<string, DiscoveryEchoCommandResult> Schlüssel: DiscoveryEchoTarget::key()
     */
    public function runMany(array $targets, int $timeoutSeconds): array;
}
