<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

final class Department extends Model
{
    protected $fillable = [
        'site_id',
        'name',
        'code',
        'specialty',
        'description',
        'archived_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (Department $department): void {
            if (blank($department->public_id)) {
                $department->public_id = (string) Str::uuid7();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'archived_at' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /** @return MorphMany<RegistryDocumentation, $this> */
    public function documentation(): MorphMany
    {
        return $this->morphMany(RegistryDocumentation::class, 'documentable');
    }

    /**
     * @param  Builder<Department>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }
}
