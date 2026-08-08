<?php

namespace App\Console\Commands;

use App\Services\Audit\AuditChainVerifier;
use App\Services\Audit\AuditChainViolation;
use Illuminate\Console\Command;

final class VerifyAuditChainCommand extends Command
{
    protected $signature = 'registry:audit-verify';

    protected $description = 'Prüft die kryptografische Integrität der vollständigen Audit-Kette';

    public function handle(AuditChainVerifier $verifier): int
    {
        try {
            $result = $verifier->verify();
        } catch (AuditChainViolation $violation) {
            $this->error('Audit-Kette ungültig: '.$violation->getMessage());
            $this->line('Datenbank-ID: '.($violation->databaseId ?? 'unbekannt'));
            $this->line('Event-ID: '.($violation->eventId ?? 'unbekannt'));
            $this->line('Zeitpunkt: '.($violation->occurredAt?->toIso8601String() ?? 'unbekannt'));

            return self::FAILURE;
        }

        $this->info("Audit-Kette gültig: {$result->verifiedEvents} Einträge geprüft.");
        $this->line('Kettenkopf: '.$result->headHash);
        if ($result->prunedThroughEventId !== null) {
            $this->line("Aufbewahrungs-Checkpoint bis Datenbank-ID: {$result->prunedThroughEventId}");
        }

        return self::SUCCESS;
    }
}
