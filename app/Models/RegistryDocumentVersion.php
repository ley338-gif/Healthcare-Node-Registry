<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\RegistryDocumentVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $public_id
 * @property int $registry_document_id
 * @property int $version_number
 * @property string $original_filename
 * @property string $stored_filename
 * @property string $storage_disk
 * @property string $storage_path
 * @property string $mime_type
 * @property string $file_extension
 * @property int $size_bytes
 * @property string $sha256
 * @property int $uploaded_by
 * @property CarbonImmutable $uploaded_at
 * @property string|null $change_note
 * @property string $malware_scan_status
 * @property string|null $malware_scan_message
 * @property array<string, mixed> $metadata
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable $updated_at
 */
final class RegistryDocumentVersion extends Model
{
    /** @use HasFactory<RegistryDocumentVersionFactory> */
    use HasFactory;

    protected $fillable = [
        'version_number',
        'original_filename',
        'stored_filename',
        'storage_disk',
        'storage_path',
        'mime_type',
        'file_extension',
        'size_bytes',
        'sha256',
        'uploaded_by',
        'uploaded_at',
        'change_note',
        'malware_scan_status',
        'malware_scan_message',
        'metadata',
    ];

    protected static function booted(): void
    {
        self::creating(function (RegistryDocumentVersion $version): void {
            if (blank($version->public_id)) {
                $version->public_id = (string) Str::uuid7();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'size_bytes' => 'integer',
            'uploaded_at' => 'immutable_datetime',
            'metadata' => 'array',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<RegistryDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(RegistryDocument::class, 'registry_document_id');
    }

    /** @return BelongsTo<User, $this> */
    public function uploadedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
