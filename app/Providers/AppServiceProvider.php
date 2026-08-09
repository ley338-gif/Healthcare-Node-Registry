<?php

namespace App\Providers;

use App\Services\Documents\ClamAvMalwareScanner;
use App\Services\Documents\MalwareScanner;
use App\Services\Documents\UnavailableMalwareScanner;
use App\Services\WorkerHeartbeat;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\Looping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MalwareScanner::class, fn (): MalwareScanner => config('registry_documents.malware_scanner.enabled')
            ? new ClamAvMalwareScanner
            : new UnavailableMalwareScanner);
    }

    public function boot(): void
    {
        DB::prohibitDestructiveCommands(app()->isProduction());

        Event::listen(Looping::class, function (): void {
            app(WorkerHeartbeat::class)->record();
        });
        Event::listen(JobProcessing::class, function (JobProcessing $event): void {
            $timeout = $event->job->payload()['timeout'] ?? null;
            app(WorkerHeartbeat::class)->recordBusy(is_numeric($timeout) ? (int) $timeout : null);
        });
        Event::listen([JobProcessed::class, JobExceptionOccurred::class], function (): void {
            app(WorkerHeartbeat::class)->record();
        });
    }
}
