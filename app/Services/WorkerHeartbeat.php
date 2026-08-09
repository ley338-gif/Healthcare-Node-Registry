<?php

namespace App\Services;

use RuntimeException;

final class WorkerHeartbeat
{
    public function record(): void
    {
        $this->write(null);
    }

    public function recordBusy(?int $jobTimeoutSeconds): void
    {
        $timeout = max(1, $jobTimeoutSeconds ?? (int) config('worker.default_job_timeout_seconds', 60));
        $grace = max(0, (int) config('worker.job_timeout_grace_seconds', 15));

        $this->write(time() + $timeout + $grace);
    }

    /** @return array{recorded_at: int, busy_until: int|null}|null */
    public function status(): ?array
    {
        $value = @file_get_contents($this->path());
        if ($value === false) {
            return null;
        }

        $decoded = json_decode($value, true);
        if (! is_array($decoded) || ! is_int($decoded['recorded_at'] ?? null)) {
            return null;
        }

        $busyUntil = $decoded['busy_until'] ?? null;
        if ($busyUntil !== null && ! is_int($busyUntil)) {
            return null;
        }

        return ['recorded_at' => $decoded['recorded_at'], 'busy_until' => $busyUntil];
    }

    public function ageSeconds(): ?int
    {
        $status = $this->status();

        return $status === null ? null : max(0, time() - $status['recorded_at']);
    }

    public function isBusy(): bool
    {
        $busyUntil = $this->status()['busy_until'] ?? null;

        return $busyUntil !== null && $busyUntil >= time();
    }

    public function isFresh(): bool
    {
        $age = $this->ageSeconds();

        return $age !== null
            && ($age <= max(1, (int) config('worker.heartbeat_max_age_seconds', 15)) || $this->isBusy());
    }

    private function write(?int $busyUntil): void
    {
        $path = $this->path();
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0750, true) && ! is_dir($directory)) {
            throw new RuntimeException("Worker-Heartbeat-Verzeichnis konnte nicht angelegt werden: {$directory}");
        }

        $payload = json_encode([
            'recorded_at' => time(),
            'busy_until' => $busyUntil,
        ], JSON_THROW_ON_ERROR);

        if (file_put_contents($path, $payload, LOCK_EX) === false) {
            throw new RuntimeException("Worker-Heartbeat konnte nicht geschrieben werden: {$path}");
        }
    }

    private function path(): string
    {
        return (string) config('worker.heartbeat_path', storage_path('framework/worker-heartbeat'));
    }
}
