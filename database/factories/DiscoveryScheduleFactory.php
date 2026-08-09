<?php

namespace Database\Factories;

use App\Models\DiscoverySchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DiscoverySchedule> */
final class DiscoveryScheduleFactory extends Factory
{
    protected $model = DiscoverySchedule::class;

    public function definition(): array
    {
        return [
            'name' => 'Geplanter Discovery-Lauf',
            'frequency' => DiscoverySchedule::FREQUENCY_DAILY,
            'scheduled_time' => '02:00',
            'day_of_week' => null,
            'timezone' => 'Europe/Berlin',
            'configuration' => [
                'name' => 'Geplanter Discovery-Lauf',
                'ip_range' => '192.168.20.0/29',
                'exclude_ips' => [],
                'scan_options' => [
                    'ping_enabled' => true,
                    'reverse_dns_enabled' => true,
                    'tcp_scan_enabled' => true,
                    'dicom_check_enabled' => true,
                    'scan_unresponsive_hosts' => false,
                    'max_parallel_hosts' => 4,
                    'timeout_seconds' => 2,
                    'retries' => 1,
                    'profile' => 'standard',
                ],
                'ports' => [['port' => 11112, 'protocol' => 'tcp', 'label' => 'DICOM', 'is_dicom_candidate' => true, 'enabled' => true]],
                'ae_candidates' => [['ae_title' => 'HNR_DISCOVERY', 'role' => 'calling', 'source' => 'default']],
            ],
            'active' => true,
            'next_run_at' => now()->addDay(),
            'created_by' => null,
        ];
    }
}
