<?php

namespace App\Services\Audit;

use Carbon\CarbonInterface;

final class AuditChainHasher
{
    public const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    /** @param array<string, mixed> $metadata */
    public function hash(
        string $eventId,
        string $eventType,
        string $actorType,
        ?string $subjectType,
        ?string $subjectPublicId,
        array $metadata,
        CarbonInterface $occurredAt,
        CarbonInterface $retentionUntil,
        string $previousHash,
    ): string {
        return hash('sha256', json_encode($this->canonicalize([
            'actor_type' => $actorType,
            'event_id' => $eventId,
            'event_type' => $eventType,
            'metadata' => $metadata,
            'occurred_at' => $occurredAt->clone()->utc()->format('Y-m-d\TH:i:s.u\Z'),
            'previous_hash' => $previousHash,
            'retention_until' => $retentionUntil->clone()->utc()->format('Y-m-d\TH:i:s.u\Z'),
            'subject_public_id' => $subjectPublicId,
            'subject_type' => $subjectType,
        ]), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
        }
        ksort($value, SORT_STRING);

        return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
    }
}
