<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * @property int $id
 * @property string $event_id
 * @property string $event_type
 * @property string $actor_type
 * @property string|null $subject_type
 * @property string|null $subject_public_id
 * @property array<string, mixed> $metadata
 * @property CarbonImmutable $occurred_at
 * @property string $previous_hash
 * @property string $event_hash
 * @property CarbonImmutable $retention_until
 */
final class SecurityEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'event_type',
        'actor_type',
        'subject_type',
        'subject_public_id',
        'metadata',
        'occurred_at',
        'previous_hash',
        'event_hash',
        'retention_until',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'immutable_datetime',
            'retention_until' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        self::updating(static function (): never {
            throw new LogicException('Audit events are immutable.');
        });
        self::deleting(static function (): never {
            throw new LogicException('Audit events may only expire through the controlled retention process.');
        });
    }
}
