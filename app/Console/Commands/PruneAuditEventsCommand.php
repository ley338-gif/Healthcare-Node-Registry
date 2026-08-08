<?php

namespace App\Console\Commands;

use App\Services\Audit\AuditChainViolation;
use App\Services\Audit\AuditRetentionPruner;
use Illuminate\Console\Command;

final class PruneAuditEventsCommand extends Command
{
    protected $signature = 'registry:audit-prune {--limit= : Maximale Anzahl je Lauf}';

    protected $description = 'Entfernt ausschließlich abgelaufene älteste Audit-Einträge und bewahrt den Ketten-Checkpoint';

    public function handle(AuditRetentionPruner $pruner): int
    {
        $limit = $this->option('limit');
        if ($limit !== null && (! ctype_digit((string) $limit) || (int) $limit < 1)) {
            $this->error('Das Limit muss eine positive Ganzzahl sein.');

            return self::INVALID;
        }

        try {
            $deleted = $pruner->prune($limit === null ? null : (int) $limit);
        } catch (AuditChainViolation $violation) {
            $this->error('Audit-Kette ungültig; es wurden keine Einträge entfernt: '.$violation->getMessage());

            return self::FAILURE;
        }
        $this->info("{$deleted} abgelaufene Audit-Einträge kontrolliert entfernt.");

        return self::SUCCESS;
    }
}
