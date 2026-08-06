<?php

namespace App\Models;

use Database\Factories\DiscoveredPortFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $discovered_host_id
 * @property int $port
 * @property string $protocol
 * @property string|null $service_name
 * @property bool $is_open
 * @property bool $is_dicom_candidate
 * @property int|null $response_time_ms
 * @property string|null $banner
 */
final class DiscoveredPort extends Model
{
    /** @use HasFactory<DiscoveredPortFactory> */
    use HasFactory;

    protected $fillable = [
        'discovered_host_id',
        'port',
        'protocol',
        'service_name',
        'is_open',
        'is_dicom_candidate',
        'response_time_ms',
        'banner',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'is_open' => 'boolean',
            'is_dicom_candidate' => 'boolean',
            'response_time_ms' => 'integer',
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
