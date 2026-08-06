<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\DiscoveredHostFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $public_id
 * @property int $discovery_run_id
 * @property string $ip_address
 * @property string|null $hostname
 * @property bool $is_reachable
 * @property int|null $ping_latency_ms
 * @property string|null $reverse_dns
 * @property string $status
 * @property string $confidence_score
 * @property int $confidence_percentage
 * @property string|null $suggested_system_type
 * @property int|null $system_id
 * @property CarbonImmutable|null $last_seen_at
 */
final class DiscoveredHost extends Model
{
    /** @use HasFactory<DiscoveredHostFactory> */
    use HasFactory;

    public const STATUS_DISCOVERED = 'discovered';

    public const STATUS_REVIEWING = 'reviewing';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_IGNORED = 'ignored';

    /**
     * @var list<string>
     */
    public const STATUSES = [
        self::STATUS_DISCOVERED,
        self::STATUS_REVIEWING,
        self::STATUS_CONFIRMED,
        self::STATUS_IGNORED,
    ];

    public const CONFIDENCE_UNKNOWN = 'unknown';

    public const CONFIDENCE_VERY_LOW = 'very_low';

    public const CONFIDENCE_LOW = 'low';

    public const CONFIDENCE_MEDIUM = 'medium';

    public const CONFIDENCE_HIGH = 'high';

    public const CONFIDENCE_VERY_HIGH = 'very_high';

    /**
     * @var list<string>
     */
    public const CONFIDENCE_LEVELS = [
        self::CONFIDENCE_UNKNOWN,
        self::CONFIDENCE_VERY_LOW,
        self::CONFIDENCE_LOW,
        self::CONFIDENCE_MEDIUM,
        self::CONFIDENCE_HIGH,
        self::CONFIDENCE_VERY_HIGH,
    ];

    protected $fillable = [
        'discovery_run_id',
        'ip_address',
        'hostname',
        'is_reachable',
        'ping_latency_ms',
        'reverse_dns',
        'status',
        'confidence_score',
        'confidence_percentage',
        'suggested_system_type',
        'system_id',
        'last_seen_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (DiscoveredHost $host): void {
            if (blank($host->public_id)) {
                $host->public_id = (string) Str::uuid7();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'is_reachable' => 'boolean',
            'ping_latency_ms' => 'integer',
            'confidence_percentage' => 'integer',
            'last_seen_at' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return BelongsTo<DiscoveryRun, $this>
     */
    public function discoveryRun(): BelongsTo
    {
        return $this->belongsTo(DiscoveryRun::class);
    }

    /**
     * @return BelongsTo<System, $this>
     */
    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    /**
     * @return HasMany<DiscoveredPort, $this>
     */
    public function ports(): HasMany
    {
        return $this->hasMany(DiscoveredPort::class);
    }

    /**
     * @return HasMany<DicomDiscoveryResult, $this>
     */
    public function dicomResults(): HasMany
    {
        return $this->hasMany(DicomDiscoveryResult::class);
    }

    /**
     * @return HasMany<DiscoveryClassificationEvidence, $this>
     */
    public function classificationEvidence(): HasMany
    {
        return $this->hasMany(DiscoveryClassificationEvidence::class);
    }
}
