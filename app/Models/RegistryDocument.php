<?php

namespace App\Models;

use App\Support\RegistryDocumentCategory;
use App\Support\RegistryDocumentValidityStatus;
use Carbon\CarbonImmutable;
use Database\Factories\RegistryDocumentFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
 * @property RegistryDocumentCategory $category
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
 * @property-read string $validity_status
 * @property-read string $validity_status_label
 */
final class RegistryDocument extends Model
{
    /** @use HasFactory<RegistryDocumentFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $appends = ['validity_status', 'validity_status_label'];

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
            'category' => RegistryDocumentCategory::class,
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

    /** @return Attribute<string, never> */
    protected function validityStatus(): Attribute
    {
        return Attribute::get(fn (): string => $this->resolveValidityStatus()->value);
    }

    /** @return Attribute<string, never> */
    protected function validityStatusLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->resolveValidityStatus()->label());
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

    private function resolveValidityStatus(): RegistryDocumentValidityStatus
    {
        if ($this->archived_at !== null || $this->status === 'archived') {
            return RegistryDocumentValidityStatus::Archived;
        }
        if ($this->valid_until === null) {
            return RegistryDocumentValidityStatus::Undated;
        }

        $today = CarbonImmutable::today();
        if ($this->valid_until->isBefore($today)) {
            return RegistryDocumentValidityStatus::Expired;
        }
        $warningDays = max(0, (int) config('registry_documents.expiry_warning_days'));
        if ($this->valid_until->lessThanOrEqualTo($today->addDays($warningDays))) {
            return RegistryDocumentValidityStatus::ExpiringSoon;
        }

        return RegistryDocumentValidityStatus::Active;
    }
}
