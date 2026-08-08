<?php

namespace App\Services\Audit;

use App\Models\SecurityEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final readonly class AuditChainWriter
{
    public function __construct(private AuditChainHasher $hasher) {}

    /** @param array<string, mixed> $metadata */
    public function append(
        string $eventId,
        string $eventType,
        string $actorType,
        ?string $subjectType,
        ?string $subjectPublicId,
        array $metadata,
        CarbonImmutable $occurredAt,
    ): SecurityEvent {
        $occurredAt = $occurredAt->setMicrosecond(0);

        return DB::transaction(function () use ($eventId, $eventType, $actorType, $subjectType, $subjectPublicId, $metadata, $occurredAt): SecurityEvent {
            $state = DB::table('audit_chain_state')->where('id', 1)->lockForUpdate()->first();
            if ($state === null) {
                throw new RuntimeException('Audit chain state is missing. Run the database migrations before writing audit events.');
            }

            $previousHash = (string) $state->last_event_hash;
            $retentionUntil = $occurredAt->addDays((int) config('audit.retention_days'));
            $eventHash = $this->hasher->hash(
                $eventId,
                $eventType,
                $actorType,
                $subjectType,
                $subjectPublicId,
                $metadata,
                $occurredAt,
                $retentionUntil,
                $previousHash,
            );

            $event = SecurityEvent::query()->create([
                'event_id' => $eventId,
                'event_type' => $eventType,
                'actor_type' => $actorType,
                'subject_type' => $subjectType,
                'subject_public_id' => $subjectPublicId,
                'metadata' => $metadata,
                'occurred_at' => $occurredAt,
                'previous_hash' => $previousHash,
                'event_hash' => $eventHash,
                'retention_until' => $retentionUntil,
            ]);

            DB::table('audit_chain_state')->where('id', 1)->update([
                'last_event_id' => $event->id,
                'last_event_hash' => $eventHash,
                'updated_at' => now(),
            ]);

            return $event;
        });
    }
}
