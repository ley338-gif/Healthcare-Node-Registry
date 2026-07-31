<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\DiagnosticTestRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $public_id
 * @property int|null $user_id
 * @property int $dicom_node_id
 * @property int $system_id
 * @property string $test_type
 * @property string $status
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable $finished_at
 * @property int $duration_ms
 * @property int|null $result_count
 * @property string $target_host
 * @property int $target_port
 * @property string|null $calling_ae_title
 * @property string|null $called_ae_title
 * @property string $summary
 * @property list<array<string, mixed>> $steps
 * @property array<string, mixed> $details
 * @property list<string> $warnings
 * @property list<string> $errors
 * @property string|null $sanitized_log_excerpt
 */
final class DiagnosticTestRun extends Model
{
    /** @use HasFactory<DiagnosticTestRunFactory> */
    use HasFactory;

    protected $fillable = [
        'public_id',
        'user_id',
        'dicom_node_id',
        'system_id',
        'test_type',
        'status',
        'started_at',
        'finished_at',
        'duration_ms',
        'result_count',
        'target_host',
        'target_port',
        'calling_ae_title',
        'called_ae_title',
        'summary',
        'steps',
        'details',
        'warnings',
        'errors',
        'sanitized_log_excerpt',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
            'duration_ms' => 'integer',
            'result_count' => 'integer',
            'target_port' => 'integer',
            'steps' => 'array',
            'details' => 'array',
            'warnings' => 'array',
            'errors' => 'array',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<DicomNode, $this> */
    public function dicomNode(): BelongsTo
    {
        return $this->belongsTo(DicomNode::class);
    }

    /** @return BelongsTo<System, $this> */
    public function system(): BelongsTo
    {
        return $this->belongsTo(System::class);
    }
}
