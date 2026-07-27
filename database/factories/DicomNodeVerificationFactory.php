<?php

namespace Database\Factories;

use App\Models\DicomNode;
use App\Models\DicomNodeVerification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DicomNodeVerification>
 */
final class DicomNodeVerificationFactory extends Factory
{
    protected $model = DicomNodeVerification::class;

    public function definition(): array
    {
        $successful = fake()->boolean(80);

        return [
            'dicom_node_id' => DicomNode::factory(),
            'triggered_by_user_id' => User::factory(),
            'status' => $successful
                ? 'success'
                : fake()->randomElement([
                    'timeout',
                    'unreachable',
                    'failed',
                    'error',
                ]),
            'successful' => $successful,
            'duration_ms' => fake()->numberBetween(10, 5000),
            'exit_code' => $successful ? 0 : 1,
            'message' => $successful
                ? 'C-ECHO erfolgreich.'
                : 'C-ECHO fehlgeschlagen.',
            'verified_at' => now(),
        ];
    }
}
