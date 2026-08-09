<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $public_id
 * @property string $code
 * @property string $label
 * @property int $sort_order
 * @property bool $is_active
 * @property bool $is_protected
 */
final class SystemStatus extends Model
{
    protected $fillable = ['code', 'label', 'sort_order', 'is_active'];

    protected static function booted(): void
    {
        self::creating(function (SystemStatus $status): void {
            if (blank($status->public_id)) {
                $status->public_id = (string) Str::uuid7();
            }
        });
    }

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_active' => 'boolean', 'is_protected' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return HasMany<System, $this> */
    public function systems(): HasMany
    {
        return $this->hasMany(System::class, 'status', 'code');
    }

    /** @param Builder<SystemStatus> $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('label');
    }
}
