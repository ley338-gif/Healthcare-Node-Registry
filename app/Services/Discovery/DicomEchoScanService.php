<?php

namespace App\Services\Discovery;

/**
 * Phase C: begrenzte DICOM-C-ECHO-Tests je Host/Port mit den
 * konfigurierten AE-Titel-Kandidaten. Kein unbegrenztes Brute-Forcing:
 * die Anzahl der Kombinationen wird vor der Ausführung hart begrenzt.
 */
final class DicomEchoScanService
{
    public function __construct(
        private readonly DiscoveryEchoCommandRunner $runner = new NativeDiscoveryEchoCommandRunner,
        private readonly int $maxAttemptsPerPort = 0,
    ) {}

    /**
     * @param  list<DiscoveryEchoTarget>  $targets
     * @return array<string, DiscoveryEchoCommandResult>
     */
    public function testMany(array $targets, int $timeoutSeconds, int $retries = 0): array
    {
        $maxAttemptsPerPort = $this->maxAttemptsPerPort > 0
            ? $this->maxAttemptsPerPort
            : (int) config('discovery.max_ae_attempts_per_port', 5);

        $bounded = $this->boundPerPort($targets, $maxAttemptsPerPort);

        $results = [];
        $pending = $bounded;

        for ($attempt = 0; $attempt <= max(0, min(3, $retries)) && $pending !== []; $attempt++) {
            $attemptResults = $this->runner->runMany($pending, $timeoutSeconds);
            $pending = [];

            foreach ($attemptResults as $key => $result) {
                $results[$key] = $result;
                if (! $result->echoSuccessful) {
                    $pending[] = $result->target;
                }
            }
        }

        return $results;
    }

    /**
     * @param  list<DiscoveryEchoTarget>  $targets
     * @return list<DiscoveryEchoTarget>
     */
    private function boundPerPort(array $targets, int $maxAttemptsPerPort): array
    {
        $countsByPort = [];
        $bounded = [];

        foreach ($targets as $target) {
            $portKey = "{$target->host}:{$target->port}";
            $countsByPort[$portKey] ??= 0;

            if ($countsByPort[$portKey] >= $maxAttemptsPerPort) {
                continue;
            }

            $countsByPort[$portKey]++;
            $bounded[] = $target;
        }

        return $bounded;
    }
}
