<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\DiscoveryRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property string|null $location
 * @property string|null $department
 * @property string $ip_range
 * @property array<int,string>|null $exclude_ips
 * @property string $status
 * @property int $progress_percentage
 * @property int $total_ips
 * @property int $processed_ips
 * @property int $found_hosts_count
 * @property int $dicom_candidates_count
 * @property array<string,mixed>|null $scan_options
 * @property CarbonImmutable|null $started_at
 * @property CarbonImmutable|null $finished_at
 * @property int|null $created_by
 * @property string|null $description
 * @property string|null $error_message
 */
final class DiscoveryRun extends Model
{
    /** @use HasFactory<DiscoveryRunFactory> */
    use HasFactory;

    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_CANCELLING = 'cancelling';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PARTIALLY_FAILED = 'partially_failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_FAILED = 'failed';

    /**
     * @var list<string>
     */
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_RUNNING,
        self::STATUS_CANCELLING,
        self::STATUS_COMPLETED,
        self::STATUS_PARTIALLY_FAILED,
        self::STATUS_CANCELLED,
        self::STATUS_FAILED,
    ];

    /**
     * @var list<string>
     */
    public const ACTIVE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_RUNNING,
        self::STATUS_CANCELLING,
    ];

    protected $fillable = [
        'name',
        'location',
        'department',
        'ip_range',
        'exclude_ips',
        'status',
        'progress_percentage',
        'total_ips',
        'processed_ips',
        'found_hosts_count',
        'dicom_candidates_count',
        'scan_options',
        'started_at',
        'finished_at',
        'created_by',
        'description',
        'error_message',
    ];

    protected static function booted(): void
    {
        self::creating(function (DiscoveryRun $run): void {
            if (blank($run->public_id)) {
                $run->public_id = (string) Str::uuid7();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'exclude_ips' => 'array',
            'scan_options' => 'array',
            'progress_percentage' => 'integer',
            'total_ips' => 'integer',
            'processed_ips' => 'integer',
            'found_hosts_count' => 'integer',
            'dicom_candidates_count' => 'integer',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<DiscoveredHost, $this>
     */
    public function hosts(): HasMany
    {
        return $this->hasMany(DiscoveredHost::class);
    }

    /**
     * @return HasMany<DiscoveryExclusion, $this>
     */
    public function exclusions(): HasMany
    {
        return $this->hasMany(DiscoveryExclusion::class);
    }

    /**
     * @return HasMany<DiscoveryPort, $this>
     */
    public function ports(): HasMany
    {
        return $this->hasMany(DiscoveryPort::class);
    }

    /**
     * @return HasMany<DiscoveryAeCandidate, $this>
     */
    public function aeCandidates(): HasMany
    {
        return $this->hasMany(DiscoveryAeCandidate::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }
}
