<?php

namespace App\Models;

use Database\Factories\DiscoveryAeCandidateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $discovery_run_id
 * @property string $ae_title
 * @property string $role
 * @property string $source
 */
final class DiscoveryAeCandidate extends Model
{
    /** @use HasFactory<DiscoveryAeCandidateFactory> */
    use HasFactory;

    public const ROLE_CALLING = 'calling';

    public const ROLE_CALLED = 'called';

    /** @var list<string> */
    public const ROLES = [self::ROLE_CALLING, self::ROLE_CALLED];

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_IMPORTED = 'imported';

    public const SOURCE_REGISTRY = 'registry';

    public const SOURCE_HOSTNAME_DERIVED = 'hostname_derived';

    public const SOURCE_DEFAULT = 'default';

    /** @var list<string> */
    public const SOURCES = [
        self::SOURCE_MANUAL,
        self::SOURCE_IMPORTED,
        self::SOURCE_REGISTRY,
        self::SOURCE_HOSTNAME_DERIVED,
        self::SOURCE_DEFAULT,
    ];

    protected $fillable = [
        'discovery_run_id',
        'ae_title',
        'role',
        'source',
    ];

    protected static function booted(): void
    {
        self::creating(function (DiscoveryAeCandidate $candidate): void {
            $candidate->ae_title = strtoupper(trim($candidate->ae_title));
        });
    }

    /**
     * @return BelongsTo<DiscoveryRun, $this>
     */
    public function discoveryRun(): BelongsTo
    {
        return $this->belongsTo(DiscoveryRun::class);
    }
}
