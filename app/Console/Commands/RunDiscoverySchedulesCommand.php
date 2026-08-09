<?php

namespace App\Console\Commands;

use App\Services\Discovery\DiscoveryScheduleService;
use Illuminate\Console\Command;

final class RunDiscoverySchedulesCommand extends Command
{
    protected $signature = 'discovery:run-schedules';

    protected $description = 'Dispatch due recurring Discovery schedules';

    public function handle(DiscoveryScheduleService $service): int
    {
        $count = $service->dispatchDue();
        $this->info("{$count} Discovery-Zeitplan/Zeitpläne gestartet.");

        return self::SUCCESS;
    }
}
