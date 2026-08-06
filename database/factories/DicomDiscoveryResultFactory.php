<?php

namespace Database\Factories;

use App\Models\DicomDiscoveryResult;
use App\Models\DiscoveredHost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DicomDiscoveryResult>
 */
final class DicomDiscoveryResultFactory extends Factory
{
    protected $model = DicomDiscoveryResult::class;

    public function definition(): array
    {
        return [
            'discovered_host_id' => DiscoveredHost::factory(),
            'port' => 11112,
            'calling_ae' => 'HNR_DISCOVERY',
            'called_ae' => fake()->bothify('AE_####'),
            'association_successful' => true,
            'echo_successful' => true,
            'error_code' => null,
            'error_message' => null,
            'raw_response' => null,
            'response_time_ms' => fake()->numberBetween(5, 120),
        ];
    }
}
