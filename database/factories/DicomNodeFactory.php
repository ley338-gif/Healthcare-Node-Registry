<?php

namespace Database\Factories;

use App\Models\DicomNode;
use App\Models\System;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DicomNode>
 */
final class DicomNodeFactory extends Factory
{
    protected $model = DicomNode::class;

    public function definition(): array
    {
        return [
            'system_id' => System::factory(),
            'name' => fake()->randomElement([
                'PACS Store SCP',
                'RIS Worklist',
                'CT DICOM',
                'MRT DICOM',
                'Diagnostik-Viewer',
            ]),
            'ae_title' => strtoupper(
                fake()->unique()->bothify('NODE####'),
            ),
            'host' => fake()->ipv4(),
            'port' => fake()->randomElement([
                104,
                11112,
                2762,
                4006,
            ]),
            'role' => fake()->randomElement([
                'scu',
                'scp',
                'both',
            ]),
            'status' => 'active',
            'tls_enabled' => false,
            'supports_echo' => true,
            'supports_store' => fake()->boolean(),
            'supports_query' => fake()->boolean(),
            'supports_retrieve' => fake()->boolean(),
            'supports_storage_commitment' => fake()->boolean(),
            'supports_mpps' => fake()->boolean(),
            'supports_worklist' => fake()->boolean(),
            'description' => null,
            'notes' => null,
            'last_verified_at' => null,
            'last_verification_status' => null,
            'last_verification_message' => null,
            'archived_at' => null,
        ];
    }
}
