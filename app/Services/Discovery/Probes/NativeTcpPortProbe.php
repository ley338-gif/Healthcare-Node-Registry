<?php

namespace App\Services\Discovery\Probes;

/**
 * Nicht-blockierender TCP-Connect-Scan über PHP-Standardfunktionen
 * (stream_socket_client + stream_select). Bewusst kein Nmap und keine
 * Shell-Aufrufe - alle Host/Port-Kombinationen eines Batches werden
 * gleichzeitig geöffnet und über einen gemeinsamen stream_select-Loop
 * ausgewertet, begrenzt durch ein gemeinsames Zeitlimit.
 */
final class NativeTcpPortProbe implements TcpPortProbe
{
    public function scanMany(array $targets, int $timeoutSeconds): array
    {
        /** @var array<string, array<int, PortProbeResult>> $results */
        $results = [];
        foreach ($targets as $ip => $ports) {
            $results[$ip] = [];
        }

        /** @var array<int, array{socket: resource, ip: string, port: int, started: float}> $pending */
        $pending = [];

        foreach ($targets as $ip => $ports) {
            foreach ($ports as $port) {
                $errno = 0;
                $errstr = '';
                $socket = @stream_socket_client(
                    "tcp://{$ip}:{$port}",
                    $errno,
                    $errstr,
                    0,
                    STREAM_CLIENT_ASYNC_CONNECT,
                );

                if ($socket === false) {
                    $results[$ip][$port] = new PortProbeResult(false);

                    continue;
                }

                stream_set_blocking($socket, false);
                $pending[(int) $socket] = ['socket' => $socket, 'ip' => $ip, 'port' => $port, 'started' => microtime(true)];
            }
        }

        $deadline = microtime(true) + max(1, $timeoutSeconds);

        while ($pending !== [] && microtime(true) < $deadline) {
            $write = array_map(static fn (array $entry) => $entry['socket'], $pending);
            $except = $write;
            $read = [];
            $remaining = max(0.0, $deadline - microtime(true));
            $seconds = (int) floor($remaining);
            $microseconds = (int) (($remaining - $seconds) * 1_000_000);

            $changed = @stream_select($read, $write, $except, $seconds, $microseconds);

            if ($changed === false) {
                break;
            }

            foreach ($write as $socket) {
                $this->resolve($pending, $results, $socket, true);
            }

            foreach ($except as $socket) {
                $this->resolve($pending, $results, $socket, false);
            }
        }

        foreach ($pending as $entry) {
            $results[$entry['ip']][$entry['port']] = new PortProbeResult(false);
            fclose($entry['socket']);
        }

        return $results;
    }

    /**
     * @param  array<int, array{socket: resource, ip: string, port: int, started: float}>  $pending
     * @param  array<string, array<int, PortProbeResult>>  $results
     * @param  resource  $socket
     */
    private function resolve(array &$pending, array &$results, $socket, bool $checkConnection): void
    {
        $id = (int) $socket;

        if (! isset($pending[$id])) {
            return;
        }

        $entry = $pending[$id];
        $connected = $checkConnection && @stream_socket_get_name($socket, true) !== false;

        $results[$entry['ip']][$entry['port']] = new PortProbeResult(
            $connected,
            $connected ? (int) round((microtime(true) - $entry['started']) * 1000) : null,
        );

        fclose($socket);
        unset($pending[$id]);
    }
}
