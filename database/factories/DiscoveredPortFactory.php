<?php

namespace Database\Factories;

use App\Models\DiscoveredHost;
use App\Models\DiscoveredPort;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscoveredPort>
 */
final class DiscoveredPortFactory extends Factory
{
    protected $model = DiscoveredPort::class;

    public function definition(): array
    {
        return [
            'discovered_host_id' => DiscoveredHost::factory(),
            'port' => 11112,
            'protocol' => 'tcp',
            'service_name' => null,
            'is_open' => true,
            'is_dicom_candidate' => true,
            'response_time_ms' => fake()->numberBetween(1, 60),
            'banner' => null,
        ];
    }
}
