<?php

namespace App\Services\Audit;

use App\Models\SecurityEvent;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final readonly class AuditRetentionPruner
{
    public function __construct(private AuditChainVerifier $verifier) {}

    public function prune(?int $limit = null): int
    {
        $batchSize = max(1, $limit ?? (int) config('audit.prune_batch_size'));
        $configuredCutoff = now()->subDays((int) config('audit.retention_days'));

        return DB::transaction(function () use ($batchSize, $configuredCutoff): int {
            $state = DB::table('audit_chain_state')->where('id', 1)->lockForUpdate()->first();
            if ($state === null) {
                throw new RuntimeException('Audit chain state is missing.');
            }
            $this->verifier->verify();

            $expiredPrefix = [];
            foreach (SecurityEvent::query()->orderBy('id')->limit($batchSize)->get() as $event) {
                if ($event->retention_until->isFuture() || $event->occurred_at->isAfter($configuredCutoff)) {
                    break;
                }
                $expiredPrefix[] = $event;
            }

            if ($expiredPrefix === []) {
                return 0;
            }

            $anchor = $expiredPrefix[array_key_last($expiredPrefix)];
            DB::table('security_events')->whereIn('id', array_map(
                static fn (SecurityEvent $event): int => $event->id,
                $expiredPrefix,
            ))->delete();
            DB::table('audit_chain_state')->where('id', 1)->update([
                'pruned_through_event_id' => $anchor->id,
                'pruned_through_hash' => $anchor->event_hash,
                'pruned_through_occurred_at' => $anchor->occurred_at,
                'updated_at' => now(),
            ]);

            return count($expiredPrefix);
        });
    }
}
