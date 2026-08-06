<?php

namespace App\Models;

use Database\Factories\DiscoveryClassificationEvidenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $discovered_host_id
 * @property string $rule_name
 * @property string $reason
 * @property int $weight
 */
final class DiscoveryClassificationEvidence extends Model
{
    /** @use HasFactory<DiscoveryClassificationEvidenceFactory> */
    use HasFactory;

    protected $fillable = [
        'discovered_host_id',
        'rule_name',
        'reason',
        'weight',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<DiscoveredHost, $this>
     */
    public function discoveredHost(): BelongsTo
    {
        return $this->belongsTo(DiscoveredHost::class);
    }
}
