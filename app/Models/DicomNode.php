<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\DicomNodeFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $public_id
 * @property int $system_id
 * @property string $name
 * @property string $ae_title
 * @property string $host
 * @property int $port
 * @property int|null $last_verification_duration_ms
 * @property string $role
 * @property string $status
 * @property bool $tls_enabled
 * @property bool $supports_echo
 * @property bool $supports_store
 * @property bool $supports_query
 * @property bool $supports_retrieve
 * @property bool $supports_storage_commitment
 * @property bool $supports_mpps
 * @property bool $supports_worklist
 * @property CarbonImmutable|null $last_verified_at
 * @property string|null $last_verification_status
 * @property CarbonImmutable|null $archived_at
 */
final class DicomNode extends Model
{
    /** @use HasFactory<DicomNodeFactory> */
    use HasFactory;

    protected $fillable = [
        'system_id',
        'name',
        'ae_title',
        'host',
        'port',
        'role',
        'status',
        'tls_enabled',
        'supports_echo',
        'supports_store',
        'supports_query',
        'supports_retrieve',
        'supports_storage_commitment',
        'supports_mpps',
        'supports_worklist',
        'description',
        'notes',
        'last_verified_at',
        'last_verification_status',
        'last_verification_duration_ms',
        'last_verification_message',
        'archived_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (DicomNode $node): void {
            if (blank($node->public_id)) {
                $node->public_id = (string) Str::uuid7();
            }

            $node->ae_title = strtoupper(trim($node->ae_title));
        });

        self::updating(function (DicomNode $node): void {
            if ($node->isDirty('ae_title')) {
                $node->ae_title = strtoupper(trim($node->ae_title));
            }
        });
    }

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'tls_enabled' => 'boolean',
            'supports_echo' => 'boolean',
            'supports_store' => 'boolean',
            'supports_query' => 'boolean',
            'supports_retrieve' => 'boolean',
            'supports_storage_commitment' => 'boolean',
            'supports_mpps' => 'boolean',
            'supports_worklist' => 'boolean',
            'last_verified_at' => 'immutable_datetime',
            'archived_at' => 'immutable_datetime',
            'last_verification_duration_ms' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return BelongsTo<System, $this>
     */
    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    /**
     * @return HasMany<DicomNodeVerification, $this>
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(DicomNodeVerification::class);
    }

    /**
     * @return HasMany<DiagnosticTestRun, $this>
     */
    public function diagnosticTestRuns(): HasMany
    {
        return $this->hasMany(DiagnosticTestRun::class);
    }

    /**
     * @param  Builder<DicomNode>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    /**
     * @return HasMany<DicomConnection, $this>
     */
    public function outgoingConnections(): HasMany
    {
        return $this->hasMany(
            DicomConnection::class,
            'source_dicom_node_id',
        );
    }

    /**
     * @return HasMany<DicomConnection, $this>
     */
    public function incomingConnections(): HasMany
    {
        return $this->hasMany(
            DicomConnection::class,
            'target_dicom_node_id',
        );
    }

    /**
     * @return HasMany<DicomConnection, $this>
     */
    public function moveDestinationConnections(): HasMany
    {
        return $this->hasMany(
            DicomConnection::class,
            'destination_dicom_node_id',
        );
    }
}
