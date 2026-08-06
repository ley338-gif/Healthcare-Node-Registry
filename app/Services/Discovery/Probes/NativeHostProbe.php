<?php

namespace App\Services\Discovery\Probes;

use Symfony\Component\Process\Exception\ExceptionInterface;
use Symfony\Component\Process\Process;

/**
 * ICMP-Ping über das System-Binary `ping` (Symfony Process, reine
 * Argumentliste, keine Shell-Interpolation). Startet für einen Batch von
 * IP-Adressen mehrere Prozesse nicht-blockierend und wertet sie parallel
 * aus - das ist die "maximale Parallelität" für die Host-Erkennung.
 *
 * Ist `ping` nicht verfügbar oder fehlen die nötigen Capabilities
 * (CAP_NET_RAW), wird jeder Host als nicht erreichbar per ICMP markiert,
 * ohne den Lauf abzubrechen. Die Portprüfung liefert in diesem Fall die
 * eigentliche Erreichbarkeitsaussage (siehe HostDiscoveryService).
 */
final class NativeHostProbe implements HostProbe
{
    public function pingBatch(array $ips, int $timeoutSeconds): array
    {
        if ($ips === []) {
            return [];
        }

        $timeoutSeconds = max(1, $timeoutSeconds);
        /** @var array<string, Process> $processes */
        $processes = [];
        /** @var array<string, HostProbeResult> $results */
        $results = [];

        foreach ($ips as $ip) {
            $process = new Process(['ping', '-c', '1', '-W', (string) $timeoutSeconds, $ip]);
            $process->setTimeout($timeoutSeconds + 3);

            try {
                $process->start();
                $processes[$ip] = $process;
            } catch (ExceptionInterface $exception) {
                $results[$ip] = new HostProbeResult(false, null, 'ping_unavailable: '.$exception->getMessage());
            }
        }

        while (array_any($processes, static fn (Process $process): bool => $process->isRunning())) {
            usleep(50_000);
        }

        foreach ($processes as $ip => $process) {
            $results[$ip] = $this->interpret($process);
        }

        return $results;
    }

    public function reverseDns(string $ip): ?string
    {
        $hostname = @gethostbyaddr($ip);

        if ($hostname === false || $hostname === $ip) {
            return null;
        }

        return $hostname;
    }

    private function interpret(Process $process): HostProbeResult
    {
        if (! $process->isSuccessful()) {
            return new HostProbeResult(false, null, 'Keine ICMP-Antwort oder Ping nicht verfügbar.');
        }

        $output = $process->getOutput();
        $latency = null;
        if (preg_match('/time[=<]\s*([\d.]+)\s*ms/i', $output, $matches) === 1) {
            $latency = (int) round((float) $matches[1]);
        }

        return new HostProbeResult(true, $latency);
    }
}
