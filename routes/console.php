<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('registry:about', function (): void {
    $this->info('Healthcare Node Registry 0.1.0');
})->purpose('Show the registry foundation version');
