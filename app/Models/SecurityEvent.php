<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $event_id
 * @property string $event_type
 * @property string $actor_type
 * @property string|null $subject_type
 * @property string|null $subject_public_id
 * @property array<string, mixed> $metadata
 * @property CarbonImmutable $occurred_at
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
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }
}
