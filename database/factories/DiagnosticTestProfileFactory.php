<?php

namespace Database\Factories;

use App\Models\DiagnosticTestProfile;
use App\Models\DicomNode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DiagnosticTestProfile> */
final class DiagnosticTestProfileFactory extends Factory
{
    protected $model = DiagnosticTestProfile::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true), 'description' => null, 'test_type' => 'network',
            'dicom_node_id' => DicomNode::factory(), 'calling_ae_title' => 'NODE_REGISTRY',
            'configuration' => [], 'timeout_seconds' => 15, 'enabled' => true,
            'created_by' => User::factory(), 'archived_at' => null,
        ];
    }
}
