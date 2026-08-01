<?php

namespace App\Providers;

use App\Services\Documents\MalwareScanner;
use App\Services\Documents\UnavailableMalwareScanner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MalwareScanner::class, UnavailableMalwareScanner::class);
    }

    public function boot(): void
    {
        DB::prohibitDestructiveCommands(app()->isProduction());
    }
}
