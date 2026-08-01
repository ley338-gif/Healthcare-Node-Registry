<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\RegistryDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $public_id
 * @property string $documentable_type
 * @property int $documentable_id
 * @property int|null $current_version_id
 * @property string $title
 * @property string|null $description
 * @property string $category
 * @property string $visibility
 * @property string $status
 * @property CarbonImmutable|null $valid_from
 * @property CarbonImmutable|null $valid_until
 * @property string|null $contract_reference
 * @property list<string> $tags
 * @property int $created_by
 * @property int $updated_by
 * @property CarbonImmutable|null $archived_at
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
final class RegistryDocument extends Model
{
    /** @use HasFactory<RegistryDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'category',
        'visibility',
        'status',
        'valid_from',
        'valid_until',
        'contract_reference',
        'tags',
        'current_version_id',
        'created_by',
        'updated_by',
        'archived_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (RegistryDocument $document): void {
            if (blank($document->public_id)) {
                $document->public_id = (string) Str::uuid7();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'valid_from' => 'immutable_date',
            'valid_until' => 'immutable_date',
            'archived_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return MorphTo<Model, $this> */
    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return HasMany<RegistryDocumentVersion, $this> */
    public function versions(): HasMany
    {
        return $this->hasMany(RegistryDocumentVersion::class);
    }

    /** @return BelongsTo<RegistryDocumentVersion, $this> */
    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(RegistryDocumentVersion::class, 'current_version_id');
    }

    /** @return BelongsTo<User, $this> */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updatedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
