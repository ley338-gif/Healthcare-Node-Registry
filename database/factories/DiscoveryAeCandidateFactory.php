<?php

namespace Database\Factories;

use App\Models\DiscoveryAeCandidate;
use App\Models\DiscoveryRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscoveryAeCandidate>
 */
final class DiscoveryAeCandidateFactory extends Factory
{
    protected $model = DiscoveryAeCandidate::class;

    public function definition(): array
    {
        return [
            'discovery_run_id' => DiscoveryRun::factory(),
            'ae_title' => 'HNR_DISCOVERY',
            'role' => DiscoveryAeCandidate::ROLE_CALLING,
            'source' => DiscoveryAeCandidate::SOURCE_DEFAULT,
        ];
    }
}
