<?php

namespace Database\Factories;

use App\Models\DiscoveryAllowedNetwork;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscoveryAllowedNetwork>
 */
final class DiscoveryAllowedNetworkFactory extends Factory
{
    protected $model = DiscoveryAllowedNetwork::class;

    public function definition(): array
    {
        return [
            'cidr' => '192.168.0.0/16',
            'description' => 'RFC1918 Testnetz',
            'active' => true,
            'created_by' => null,
        ];
    }
}
