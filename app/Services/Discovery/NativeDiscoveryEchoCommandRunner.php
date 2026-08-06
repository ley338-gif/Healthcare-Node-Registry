<?php

namespace App\Services\Discovery;

use Symfony\Component\Process\Exception\ExceptionInterface;
use Symfony\Component\Process\Process;

/**
 * Führt DCMTK `echoscu` für beliebige Host/Port/AE-Kombinationen aus.
 * Bewusst getrennt von App\Services\Dicom\NativeDicomEchoCommandRunner,
 * das fest an einen verifizierten DicomNode und den Calling AE Title
 * "NODE_REGISTRY" gebunden ist und die produktive Node-Verifizierung
 * absichert - Discovery darf diesen Pfad nicht verändern.
 *
 * Argumentliste statt Shell-String, harte Prozess-Timeouts, keine freien
 * Benutzerparameter jenseits validierter Host/Port/AE-Werte.
 */
final class NativeDiscoveryEchoCommandRunner implements DiscoveryEchoCommandRunner
{
    public function runMany(array $targets, int $timeoutSeconds): array
    {
        if ($targets === []) {
            return [];
        }

        $timeoutSeconds = max(1, $timeoutSeconds);
        /** @var array<string, array{process: Process, target: DiscoveryEchoTarget, started: float}> $processes */
        $processes = [];
        /** @var array<string, DiscoveryEchoCommandResult> $results */
        $results = [];

        foreach ($targets as $target) {
            $process = new Process([
                '/usr/bin/echoscu',
                '-v',
                '-aet', $target->callingAeTitle,
                '-aec', $target->calledAeTitle,
                '-to', (string) $timeoutSeconds,
                '-ta', (string) $timeoutSeconds,
                '-td', (string) $timeoutSeconds,
                $target->host,
                (string) $target->port,
            ]);
            $process->setTimeout($timeoutSeconds + 5);

            try {
                $process->start();
                $processes[$target->key()] = ['process' => $process, 'target' => $target, 'started' => microtime(true)];
            } catch (ExceptionInterface $exception) {
                $results[$target->key()] = new DiscoveryEchoCommandResult(
                    $target,
                    false,
                    false,
                    'process_error',
                    'DCMTK-Prozess konnte nicht gestartet werden: '.$exception->getMessage(),
                    '',
                    0,
                );
            }
        }

        while (array_any($processes, static fn (array $entry): bool => $entry['process']->isRunning())) {
            usleep(50_000);
        }

        foreach ($processes as $key => $entry) {
            $results[$key] = $this->interpret($entry['process'], $entry['target'], (int) round((microtime(true) - $entry['started']) * 1000));
        }

        return $results;
    }

    private function interpret(Process $process, DiscoveryEchoTarget $target, int $durationMs): DiscoveryEchoCommandResult
    {
        $output = mb_substr(trim($process->getOutput()."\n".$process->getErrorOutput()), 0, 4000);
        $normalized = strtolower($output);

        if ($process->isSuccessful()) {
            return new DiscoveryEchoCommandResult($target, true, true, null, null, $output, $durationMs);
        }

        [$errorCode, $errorMessage, $associationSuccessful] = match (true) {
            $process->getExitCode() === null => ['timeout', 'Zeitüberschreitung beim C-ECHO.', false],
            str_contains($normalized, 'timeout') || str_contains($normalized, 'timed out') => ['timeout', 'Zeitüberschreitung beim C-ECHO.', false],
            str_contains($normalized, 'connection refused') => ['connection_refused', 'TCP-Verbindung wurde vom Ziel abgelehnt.', false],
            str_contains($normalized, 'called ae') && str_contains($normalized, 'unknown') => ['called_ae_unknown', 'Called AE Title ist dem Ziel nicht bekannt.', false],
            str_contains($normalized, 'calling ae') && str_contains($normalized, 'not authorized') => ['calling_ae_unauthorized', 'Calling AE Title ist am Ziel nicht autorisiert.', false],
            str_contains($normalized, 'association rejected') || str_contains($normalized, 'association abort') => ['association_rejected', 'DICOM Association wurde abgelehnt.', false],
            default => ['dicom_failure', 'C-ECHO fehlgeschlagen.', false],
        };

        return new DiscoveryEchoCommandResult($target, $associationSuccessful, false, $errorCode, $errorMessage, $output, $durationMs);
    }
}
