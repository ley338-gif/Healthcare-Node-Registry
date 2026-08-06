<?php

namespace Database\Factories;

use App\Models\DiscoveredHost;
use App\Models\DiscoveryRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscoveredHost>
 */
final class DiscoveredHostFactory extends Factory
{
    protected $model = DiscoveredHost::class;

    public function definition(): array
    {
        return [
            'discovery_run_id' => DiscoveryRun::factory(),
            'ip_address' => fake()->unique()->ipv4(),
            'hostname' => fake()->optional()->domainWord(),
            'is_reachable' => true,
            'ping_latency_ms' => fake()->numberBetween(1, 40),
            'reverse_dns' => null,
            'status' => DiscoveredHost::STATUS_DISCOVERED,
            'confidence_score' => DiscoveredHost::CONFIDENCE_UNKNOWN,
            'confidence_percentage' => 0,
            'suggested_system_type' => null,
            'system_id' => null,
            'last_seen_at' => now(),
        ];
    }
}
