<?php

namespace App\Models;

use Database\Factories\DiscoveryPortFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Konfigurierter Scan-Port eines Discovery-Laufs (Wizard-Schritt 3).
 * Nicht zu verwechseln mit {@see DiscoveredPort}, dem Scan-Ergebnis.
 *
 * @property int $id
 * @property int $discovery_run_id
 * @property int $port
 * @property string $protocol
 * @property string|null $label
 * @property bool $is_dicom_candidate
 * @property bool $enabled
 */
final class DiscoveryPort extends Model
{
    /** @use HasFactory<DiscoveryPortFactory> */
    use HasFactory;

    protected $table = 'discovery_ports';

    protected $fillable = [
        'discovery_run_id',
        'port',
        'protocol',
        'label',
        'is_dicom_candidate',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'port' => 'integer',
            'is_dicom_candidate' => 'boolean',
            'enabled' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<DiscoveryRun, $this>
     */
    public function discoveryRun(): BelongsTo
    {
        return $this->belongsTo(DiscoveryRun::class);
    }
}
