<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class SystemNetworkInterface extends Model
{
    protected $fillable = [
        'interface_label',
        'hostname',
        'fqdn',
        'ip_address',
        'is_primary',
    ];

    protected static function booted(): void
    {
        self::creating(function (SystemNetworkInterface $interface): void {
            if (blank($interface->public_id)) {
                $interface->public_id = (string) Str::uuid7();
            }
        });
    }

    protected function casts(): array
    {
        return ['is_primary' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<System, $this> */
    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }

    public function syncLegacySystemFields(): void
    {
        $this->system->updateQuietly([
            'hostname' => $this->hostname,
            'fqdn' => $this->fqdn,
            'ip_address' => $this->ip_address,
        ]);
    }
}
