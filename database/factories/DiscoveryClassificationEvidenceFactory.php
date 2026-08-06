<?php

namespace Database\Factories;

use App\Models\DiscoveredHost;
use App\Models\DiscoveryClassificationEvidence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscoveryClassificationEvidence>
 */
final class DiscoveryClassificationEvidenceFactory extends Factory
{
    protected $model = DiscoveryClassificationEvidence::class;

    public function definition(): array
    {
        return [
            'discovered_host_id' => DiscoveredHost::factory(),
            'rule_name' => 'dicom_echo_successful',
            'reason' => 'C-ECHO war erfolgreich.',
            'weight' => 40,
        ];
    }
}
