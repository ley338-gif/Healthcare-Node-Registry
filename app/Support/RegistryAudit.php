<?php

namespace App\Support;

use App\Models\User;
use App\Services\Audit\AuditChainWriter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class RegistryAudit
{
    /** @param array<string,mixed> $metadata */
    public function record(string $eventType, Model $subject, ?User $actor, array $metadata = [], ?string $actorType = null): void
    {
        app(AuditChainWriter::class)->append(
            (string) Str::uuid7(),
            $eventType,
            $actorType ?? ($actor ? User::class : 'system'),
            $subject::class,
            $subject->getAttribute('public_id'),
            ['actor_public_id' => $actor?->public_id, ...$metadata],
            now()->toImmutable(),
        );
    }
}
