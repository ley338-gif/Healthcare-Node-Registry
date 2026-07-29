<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\DicomNodeVerificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $public_id
 * @property int $dicom_node_id
 * @property int|null $triggered_by_user_id
 * @property string $status
 * @property bool $successful
 * @property int $duration_ms
 * @property int $exit_code
 * @property string|null $message
 * @property CarbonImmutable $verified_at
 */
final class DicomNodeVerification extends Model
{
    /** @use HasFactory<DicomNodeVerificationFactory> */
    use HasFactory;

    protected $fillable = [
        'dicom_node_id',
        'triggered_by_user_id',
        'status',
        'successful',
        'duration_ms',
        'exit_code',
        'message',
        'verified_at',
    ];

    protected static function booted(): void
    {
        self::creating(
            function (DicomNodeVerification $verification): void {
                if (blank($verification->public_id)) {
                    $verification->public_id = (string) Str::uuid7();
                }
            },
        );
    }

    protected function casts(): array
    {
        return [
            'successful' => 'boolean',
            'duration_ms' => 'integer',
            'exit_code' => 'integer',
            'verified_at' => 'immutable_datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return BelongsTo<DicomNode, $this>
     */
    public function dicomNode(): BelongsTo
    {
        return $this->belongsTo(DicomNode::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function triggeredByUser(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'triggered_by_user_id',
        );
    }
}
