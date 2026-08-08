<?php

namespace App\Console\Commands;

use App\Services\Documents\RegistryDocumentRescanService;
use Illuminate\Console\Command;

final class RescanRegistryDocumentsCommand extends Command
{
    protected $signature = 'registry-documents:rescan {--limit=250 : Maximale Anzahl offener Scans}';

    protected $description = 'Scannt nicht freigegebene Dokumentversionen erneut auf Schadsoftware';

    public function handle(RegistryDocumentRescanService $service): int
    {
        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($limit === false) {
            $this->error('Die Option --limit muss eine positive Ganzzahl sein.');

            return self::INVALID;
        }

        $counts = $service->rescan($limit);
        $this->table(['Geprüft', 'Sauber', 'Infiziert', 'Fehlgeschlagen', 'Nicht erreichbar'], [[
            $counts['scanned'], $counts['clean'], $counts['infected'], $counts['failed'], $counts['unavailable'],
        ]]);

        return self::SUCCESS;
    }
}
