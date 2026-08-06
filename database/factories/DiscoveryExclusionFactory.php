<?php

namespace Database\Factories;

use App\Models\DiscoveryExclusion;
use App\Models\DiscoveryRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscoveryExclusion>
 */
final class DiscoveryExclusionFactory extends Factory
{
    protected $model = DiscoveryExclusion::class;

    public function definition(): array
    {
        return [
            'discovery_run_id' => DiscoveryRun::factory(),
            'ip_address' => fake()->unique()->ipv4(),
        ];
    }
}
