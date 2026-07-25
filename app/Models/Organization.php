<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class Organization extends Model
{
    protected $fillable = [
        'name',
        'short_name',
        'description',
        'archived_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (Organization $organization): void {
            if (blank($organization->public_id)) {
                $organization->public_id = (string) Str::uuid7();
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
     * @return HasMany<Site, $this>
     */
    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    /**
     * @param  Builder<Organization>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }
}
