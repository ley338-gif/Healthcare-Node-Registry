<?php

namespace Database\Factories;

use App\Models\DiscoveryRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscoveryRun>
 */
final class DiscoveryRunFactory extends Factory
{
    protected $model = DiscoveryRun::class;

    public function definition(): array
    {
        return [
            'name' => 'Discovery '.fake()->unique()->numerify('####'),
            'location' => fake()->optional()->city(),
            'department' => fake()->optional()->randomElement(['Radiologie', 'Kardiologie', 'IT']),
            'ip_range' => '192.168.20.0/28',
            'exclude_ips' => [],
            'status' => DiscoveryRun::STATUS_DRAFT,
            'progress_percentage' => 0,
            'total_ips' => 16,
            'processed_ips' => 0,
            'found_hosts_count' => 0,
            'dicom_candidates_count' => 0,
            'scan_options' => [
                'ping_enabled' => true,
                'reverse_dns_enabled' => true,
                'tcp_scan_enabled' => true,
                'dicom_check_enabled' => true,
                'scan_unresponsive_hosts' => false,
                'max_parallel_hosts' => 8,
                'timeout_seconds' => 2,
                'retries' => 1,
                'profile' => 'standard',
            ],
            'started_at' => null,
            'finished_at' => null,
            'created_by' => null,
            'description' => null,
            'error_message' => null,
        ];
    }
}
