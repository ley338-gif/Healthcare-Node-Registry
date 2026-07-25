<?php

namespace App\Support;

use App\Models\SecurityEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class RegistryAudit
{
    /** @param array<string,mixed> $metadata */
    public function record(string $eventType, Model $subject, ?User $actor, array $metadata = []): void
    {
        SecurityEvent::query()->create([
            'event_id' => (string) Str::uuid7(),
            'event_type' => $eventType,
            'actor_type' => $actor ? User::class : 'system',
            'subject_type' => $subject::class,
            'subject_public_id' => $subject->getAttribute('public_id'),
            'metadata' => ['actor_public_id' => $actor?->public_id, ...$metadata],
            'occurred_at' => now(),
        ]);
    }
}
