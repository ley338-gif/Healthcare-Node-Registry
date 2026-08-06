<?php

namespace App\Models;

use Database\Factories\DiscoveryExclusionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $discovery_run_id
 * @property string $ip_address
 */
final class DiscoveryExclusion extends Model
{
    /** @use HasFactory<DiscoveryExclusionFactory> */
    use HasFactory;

    protected $fillable = [
        'discovery_run_id',
        'ip_address',
    ];

    /**
     * @return BelongsTo<DiscoveryRun, $this>
     */
    public function discoveryRun(): BelongsTo
    {
        return $this->belongsTo(DiscoveryRun::class);
    }
}
