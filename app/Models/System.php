<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\SystemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $public_id
 * @property int $organization_id
 * @property int|null $site_id
 * @property int|null $department_id
 * @property string $name
 * @property string $system_type
 * @property string $status
 * @property CarbonImmutable|null $archived_at
 */
final class System extends Model
{
    /** @use HasFactory<SystemFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'site_id',
        'department_id',
        'name',
        'system_type',
        'status',
        'hostname',
        'fqdn',
        'ip_address',
        'vendor',
        'product',
        'model',
        'version',
        'operating_system',
        'operating_system_version',
        'serial_number',
        'inventory_number',
        'description',
        'notes',
        'responsible',
        'criticality',
        'archived_at',
    ];

    protected static function booted(): void
    {
        self::creating(function (System $system): void {
            if (blank($system->public_id)) {
                $system->public_id = (string) Str::uuid7();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'archived_at' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return BelongsTo<Department, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * @return HasMany<DicomNode, $this>
     */
    public function dicomNodes(): HasMany
    {
        return $this->hasMany(DicomNode::class);
    }

    /** @return HasMany<DiagnosticTestRun, $this> */
    public function diagnosticTestRuns(): HasMany
    {
        return $this->hasMany(DiagnosticTestRun::class);
    }

    /** @return MorphMany<RegistryDocumentation, $this> */
    public function documentation(): MorphMany
    {
        return $this->morphMany(RegistryDocumentation::class, 'documentable');
    }

    /** @return MorphMany<RegistryDocument, $this> */
    public function documents(): MorphMany
    {
        return $this->morphMany(RegistryDocument::class, 'documentable');
    }

    /**
     * @param  Builder<System>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }
}
