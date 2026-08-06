<?php

namespace App\Models;

use Database\Factories\DicomDiscoveryResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $discovered_host_id
 * @property int $port
 * @property string $calling_ae
 * @property string $called_ae
 * @property bool $association_successful
 * @property bool $echo_successful
 * @property string|null $error_code
 * @property string|null $error_message
 * @property array<string,mixed>|null $raw_response
 * @property int|null $response_time_ms
 */
final class DicomDiscoveryResult extends Model
{
    /** @use HasFactory<DicomDiscoveryResultFactory> */
    use HasFactory;

    protected $fillable = [
        'discovered_host_id',
        'port',
        'calling_ae',
        'called_ae',
        'association_successful',
        'echo_successful',
        'error_code',
        'error_message',
        'raw_response',
        'response_time_ms',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'association_successful' => 'boolean',
            'echo_successful' => 'boolean',
            'raw_response' => 'array',
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
