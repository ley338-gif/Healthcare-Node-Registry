<?php

namespace App\Console\Commands;

use App\Services\WorkerHeartbeat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

final class CheckWorkerHealth extends Command
{
    protected $signature = 'registry:worker-health';

    protected $description = 'Prüft Queue-Worker-Heartbeat und Datenbankverbindung.';

    public function handle(WorkerHeartbeat $heartbeat): int
    {
        try {
            DB::connection()->select('select 1');
        } catch (Throwable $exception) {
            $this->error('Worker unhealthy: Datenbank nicht erreichbar.');

            return self::FAILURE;
        }

        $age = $heartbeat->ageSeconds();
        if (! $heartbeat->isFresh()) {
            $detail = $age === null ? 'kein gültiger Heartbeat' : "Heartbeat {$age} Sekunden alt";
            $this->error("Worker unhealthy: {$detail}.");

            return self::FAILURE;
        }

        $state = $heartbeat->isBusy() ? 'beschäftigt' : 'bereit';
        $this->info("Worker healthy: {$state}, Heartbeat {$age} Sekunden alt, Datenbank erreichbar.");

        return self::SUCCESS;
    }
}
