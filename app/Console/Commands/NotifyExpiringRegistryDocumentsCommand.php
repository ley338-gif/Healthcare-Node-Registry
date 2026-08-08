<?php

namespace App\Console\Commands;

use App\Services\Documents\RegistryDocumentExpiryNotificationService;
use Illuminate\Console\Command;

final class NotifyExpiringRegistryDocumentsCommand extends Command
{
    protected $signature = 'registry-documents:notify-expiry';

    protected $description = 'Erstellt In-App-Hinweise für ablaufende und abgelaufene Registry-Dokumente';

    public function handle(RegistryDocumentExpiryNotificationService $service): int
    {
        $counts = $service->notify();
        $this->table(['Dokumente', 'Empfänger', 'Neu', 'Bereits vorhanden'], [[
            $counts['documents'], $counts['recipients'], $counts['sent'], $counts['skipped'],
        ]]);

        return self::SUCCESS;
    }
}
