<?php

namespace App\Services\Diagnostics;

interface NetworkProbe
{
    /** @return list<string> */
    public function resolve(string $host): array;

    public function connect(string $host, int $port, float $timeoutSeconds): NetworkProbeResult;
}
