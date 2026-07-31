<?php

namespace App\Services\Diagnostics;

final class NativeNetworkProbe implements NetworkProbe
{
    public function resolve(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return [$host];
        }

        $addresses = gethostbynamel($host);

        return $addresses === false ? [] : array_values(array_unique($addresses));
    }

    public function connect(string $host, int $port, float $timeoutSeconds): NetworkProbeResult
    {
        $targetHost = str_contains($host, ':') ? "[{$host}]" : $host;
        $errorCode = 0;
        $errorMessage = '';

        $socket = @stream_socket_client(
            "tcp://{$targetHost}:{$port}",
            $errorCode,
            $errorMessage,
            $timeoutSeconds,
            STREAM_CLIENT_CONNECT,
        );

        if ($socket === false) {
            return new NetworkProbeResult(false, $errorCode, $errorMessage);
        }

        fclose($socket);

        return new NetworkProbeResult(true);
    }
}
