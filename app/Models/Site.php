<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class Site extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'code',
        'street',
        'postal_code',
        'city',
        'country_code',
        'timezone',
        'description',
        'archived_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (Site $site): void {
            if (blank($site->public_id)) {
                $site->public_id = (string) Str::uuid7();
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
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany<Department, $this>
     */
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    /**
     * @param  Builder<Site>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }
}
