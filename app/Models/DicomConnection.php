<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\DicomConnectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $public_id
 * @property int $source_dicom_node_id
 * @property int $target_dicom_node_id
 * @property int|null $destination_dicom_node_id
 * @property string $name
 * @property string $service
 * @property string $status
 * @property string|null $calling_ae_title
 * @property string|null $called_ae_title
 * @property int|null $port_override
 * @property bool $tls_enabled
 * @property bool $test_enabled
 * @property string|null $description
 * @property string|null $notes
 * @property CarbonImmutable|null $archived_at
 */
final class DicomConnection extends Model
{
    /** @use HasFactory<DicomConnectionFactory> */
    use HasFactory;

    public const SERVICE_ECHO = 'echo';

    public const SERVICE_STORE = 'store';

    public const SERVICE_WORKLIST = 'worklist';

    public const SERVICE_QUERY = 'query';

    public const SERVICE_MOVE = 'move';

    public const SERVICE_GET = 'get';

    /**
     * @var list<string>
     */
    public const SERVICES = [
        self::SERVICE_ECHO,
        self::SERVICE_STORE,
        self::SERVICE_WORKLIST,
        self::SERVICE_QUERY,
        self::SERVICE_MOVE,
        self::SERVICE_GET,
    ];

    protected $fillable = [
        'source_dicom_node_id',
        'target_dicom_node_id',
        'destination_dicom_node_id',
        'name',
        'service',
        'status',
        'calling_ae_title',
        'called_ae_title',
        'port_override',
        'tls_enabled',
        'test_enabled',
        'description',
        'notes',
        'archived_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (DicomConnection $connection): void {
            if (blank($connection->public_id)) {
                $connection->public_id = (string) Str::uuid7();
            }

            $connection->normalizeAeTitles();
        });

        self::updating(function (DicomConnection $connection): void {
            $connection->normalizeAeTitles();
        });
    }

    protected function casts(): array
    {
        return [
            'port_override' => 'integer',
            'tls_enabled' => 'boolean',
            'test_enabled' => 'boolean',
            'archived_at' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return BelongsTo<DicomNode, $this>
     */
    public function sourceNode(): BelongsTo
    {
        return $this->belongsTo(
            DicomNode::class,
            'source_dicom_node_id',
        );
    }

    /**
     * @return BelongsTo<DicomNode, $this>
     */
    public function targetNode(): BelongsTo
    {
        return $this->belongsTo(
            DicomNode::class,
            'target_dicom_node_id',
        );
    }

    /**
     * @return BelongsTo<DicomNode, $this>
     */
    public function destinationNode(): BelongsTo
    {
        return $this->belongsTo(
            DicomNode::class,
            'destination_dicom_node_id',
        );
    }

    /**
     * @param  Builder<DicomConnection>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    private function normalizeAeTitles(): void
    {
        if ($this->calling_ae_title !== null) {
            $this->calling_ae_title = strtoupper(
                trim($this->calling_ae_title),
            );
        }

        if ($this->called_ae_title !== null) {
            $this->called_ae_title = strtoupper(
                trim($this->called_ae_title),
            );
        }
    }
}
