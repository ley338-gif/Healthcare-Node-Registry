<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\DiagnosticTestProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property string|null $description
 * @property string $test_type
 * @property int $dicom_node_id
 * @property string|null $calling_ae_title
 * @property array<string, mixed> $configuration
 * @property int $timeout_seconds
 * @property bool $enabled
 * @property int|null $created_by
 * @property CarbonImmutable|null $archived_at
 */
final class DiagnosticTestProfile extends Model
{
    /** @use HasFactory<DiagnosticTestProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'test_type', 'dicom_node_id', 'calling_ae_title',
        'configuration', 'timeout_seconds', 'enabled', 'created_by', 'archived_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (DiagnosticTestProfile $profile): void {
            if (blank($profile->public_id)) {
                $profile->public_id = (string) Str::uuid7();
            }
        });
    }

    protected function casts(): array
    {
        return ['configuration' => 'array', 'timeout_seconds' => 'integer', 'enabled' => 'boolean', 'archived_at' => 'immutable_datetime'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<DicomNode, $this> */
    public function dicomNode(): BelongsTo
    {
        return $this->belongsTo(DicomNode::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
