<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property string|null $description
 * @property list<array{port: int, protocol: string, label: string|null, is_dicom_candidate: bool, enabled: bool}> $ports
 * @property bool $active
 * @property bool $is_default
 * @property int|null $created_by
 */
final class DiscoveryPortProfile extends Model
{
    protected $fillable = ['name', 'description', 'ports', 'active', 'is_default', 'created_by'];

    protected static function booted(): void
    {
        self::creating(function (DiscoveryPortProfile $profile): void {
            if (blank($profile->public_id)) {
                $profile->public_id = (string) Str::uuid7();
            }
        });
    }

    protected function casts(): array
    {
        return ['ports' => 'array', 'active' => 'boolean', 'is_default' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @param Builder<DiscoveryPortProfile> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }
}
