<?php

namespace App\Services\Audit;

use App\Models\SecurityEvent;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final readonly class AuditChainVerifier
{
    public function __construct(private AuditChainHasher $hasher) {}

    public function verify(): AuditChainVerificationResult
    {
        return DB::transaction(function (): AuditChainVerificationResult {
            $state = DB::table('audit_chain_state')->where('id', 1)->sharedLock()->first();
            if ($state === null) {
                throw new RuntimeException('Audit chain state is missing.');
            }

            $expectedPreviousHash = (string) $state->pruned_through_hash;
            $lastEventId = $state->pruned_through_event_id === null ? null : (int) $state->pruned_through_event_id;
            $verified = 0;

            foreach (SecurityEvent::query()->orderBy('id')->cursor() as $event) {
                if (! hash_equals($expectedPreviousHash, $event->previous_hash)) {
                    throw $this->violation($event, 'Der Vorgänger-Hash stimmt nicht. Ein Audit-Eintrag wurde gelöscht, eingefügt oder umgeordnet.');
                }

                $calculatedHash = $this->hasher->hash(
                    $event->event_id,
                    $event->event_type,
                    $event->actor_type,
                    $event->subject_type,
                    $event->subject_public_id,
                    $event->metadata,
                    $event->occurred_at,
                    $event->retention_until,
                    $event->previous_hash,
                );
                if (! hash_equals($calculatedHash, $event->event_hash)) {
                    throw $this->violation($event, 'Der Ereignis-Hash ist ungültig. Der Audit-Eintrag wurde nachträglich verändert.');
                }

                $expectedPreviousHash = $event->event_hash;
                $lastEventId = $event->id;
                $verified++;
            }

            if (! hash_equals((string) $state->last_event_hash, $expectedPreviousHash)
                || ($state->last_event_id === null ? null : (int) $state->last_event_id) !== $lastEventId) {
                throw new AuditChainViolation(
                    'Der gespeicherte Kettenkopf stimmt nicht. Ein oder mehrere abschließende Audit-Einträge wurden gelöscht.',
                    $lastEventId,
                );
            }

            return new AuditChainVerificationResult(
                $verified,
                $expectedPreviousHash,
                $state->pruned_through_event_id === null ? null : (int) $state->pruned_through_event_id,
            );
        });
    }

    private function violation(SecurityEvent $event, string $message): AuditChainViolation
    {
        return new AuditChainViolation($message, $event->id, $event->event_id, $event->occurred_at);
    }
}
