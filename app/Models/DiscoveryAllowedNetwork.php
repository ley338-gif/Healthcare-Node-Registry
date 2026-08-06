<?php

namespace App\Models;

use Database\Factories\DiscoveryAllowedNetworkFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Von einem Administrator freigegebener Netzbereich, innerhalb dessen
 * Discovery-Läufe gestartet werden dürfen.
 *
 * @property int $id
 * @property string $public_id
 * @property string $cidr
 * @property string|null $description
 * @property bool $active
 * @property int|null $created_by
 */
final class DiscoveryAllowedNetwork extends Model
{
    /** @use HasFactory<DiscoveryAllowedNetworkFactory> */
    use HasFactory;

    protected $fillable = [
        'cidr',
        'description',
        'active',
        'created_by',
    ];

    protected static function booted(): void
    {
        self::creating(function (DiscoveryAllowedNetwork $network): void {
            if (blank($network->public_id)) {
                $network->public_id = (string) Str::uuid7();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @param  Builder<DiscoveryAllowedNetwork>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }
}
