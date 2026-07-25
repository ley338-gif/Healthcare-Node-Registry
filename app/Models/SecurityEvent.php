<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
