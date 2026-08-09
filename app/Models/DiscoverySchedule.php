<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\DiscoveryScheduleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property string $frequency
 * @property string $scheduled_time
 * @property int|null $day_of_week
 * @property string $timezone
 * @property array<string, mixed> $configuration
 * @property bool $active
 * @property CarbonImmutable $next_run_at
 * @property CarbonImmutable|null $last_dispatched_at
 * @property string|null $last_error
 * @property int|null $last_run_id
 * @property int|null $created_by
 */
final class DiscoverySchedule extends Model
{
    /** @use HasFactory<DiscoveryScheduleFactory> */
    use HasFactory;

    use SoftDeletes;

    public const FREQUENCY_DAILY = 'daily';

    public const FREQUENCY_WEEKLY = 'weekly';

    /** @var list<string> */
    public const FREQUENCIES = [self::FREQUENCY_DAILY, self::FREQUENCY_WEEKLY];

    protected $fillable = [
        'name',
        'frequency',
        'scheduled_time',
        'day_of_week',
        'timezone',
        'configuration',
        'active',
        'next_run_at',
        'last_dispatched_at',
        'last_error',
        'last_run_id',
        'created_by',
    ];

    protected static function booted(): void
    {
        self::creating(function (DiscoverySchedule $schedule): void {
            if (blank($schedule->public_id)) {
                $schedule->public_id = (string) Str::uuid7();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'active' => 'boolean',
            'day_of_week' => 'integer',
            'next_run_at' => 'immutable_datetime',
            'last_dispatched_at' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<DiscoveryRun, $this> */
    public function lastRun(): BelongsTo
    {
        return $this->belongsTo(DiscoveryRun::class, 'last_run_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
