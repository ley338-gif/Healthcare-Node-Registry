<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\RegistryDocumentationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $public_id
 * @property string $documentable_type
 * @property int $documentable_id
 * @property string $documentation_type
 * @property string $section
 * @property string $title
 * @property string|null $content
 * @property array<string, mixed> $structured_data
 * @property string $visibility
 * @property int $created_by
 * @property int $updated_by
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
final class RegistryDocumentation extends Model
{
    /** @use HasFactory<RegistryDocumentationFactory> */
    use HasFactory;

    protected $table = 'registry_documentation';

    protected $fillable = [
        'documentation_type',
        'section',
        'title',
        'content',
        'structured_data',
        'visibility',
        'created_by',
        'updated_by',
    ];

    protected static function booted(): void
    {
        self::creating(function (RegistryDocumentation $documentation): void {
            if (blank($documentation->public_id)) {
                $documentation->public_id = (string) Str::uuid7();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'structured_data' => 'array',
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
