<?php

namespace Database\Factories;

use App\Models\DiscoveryPort;
use App\Models\DiscoveryRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscoveryPort>
 */
final class DiscoveryPortFactory extends Factory
{
    protected $model = DiscoveryPort::class;

    public function definition(): array
    {
        return [
            'discovery_run_id' => DiscoveryRun::factory(),
            'port' => 11112,
            'protocol' => 'tcp',
            'label' => 'DICOM (Standard)',
            'is_dicom_candidate' => true,
            'enabled' => true,
        ];
    }
}
