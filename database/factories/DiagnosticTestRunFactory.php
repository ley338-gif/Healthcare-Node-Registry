<?php

namespace Database\Factories;

use App\Models\DiagnosticTestRun;
use App\Models\DicomNode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<DiagnosticTestRun> */
final class DiagnosticTestRunFactory extends Factory
{
    protected $model = DiagnosticTestRun::class;

    public function definition(): array
    {
        $node = DicomNode::factory();

        return [
            'public_id' => (string) Str::uuid7(),
            'user_id' => User::factory(),
            'dicom_node_id' => $node,
            'system_id' => fn (array $attributes): int => DicomNode::query()
                ->findOrFail($attributes['dicom_node_id'])
                ->system_id,
            'test_type' => 'network',
            'status' => 'success',
            'started_at' => now()->subMilliseconds(25),
            'finished_at' => now(),
            'duration_ms' => 25,
            'result_count' => null,
            'target_host' => '127.0.0.1',
            'target_port' => 104,
            'calling_ae_title' => null,
            'called_ae_title' => 'PACS',
            'summary' => 'Netzwerkverbindung erfolgreich.',
            'steps' => [],
            'details' => [],
            'warnings' => [],
            'errors' => [],
            'sanitized_log_excerpt' => null,
        ];
    }
}
