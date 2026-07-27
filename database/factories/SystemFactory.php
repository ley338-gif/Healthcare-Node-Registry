<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Organization;
use App\Models\Site;
use App\Models\System;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<System>
 */
final class SystemFactory extends Factory
{
    protected $model = System::class;

    public function definition(): array
    {
        $organization = Organization::query()->create([
            'name' => fake()->unique()->company(),
        ]);

        $site = Site::query()->create([
            'organization_id' => $organization->id,
            'name' => fake()->unique()->city().' Standort',
            'country_code' => 'DE',
            'timezone' => 'Europe/Berlin',
        ]);

        $department = Department::query()->create([
            'site_id' => $site->id,
            'name' => fake()->unique()->randomElement([
                'Radiologie',
                'Kardiologie',
                'IT',
                'Notaufnahme',
                'Nuklearmedizin',
            ]),
        ]);

        $type = fake()->randomElement([
            'pacs',
            'ris',
            'kis',
            'modality',
            'viewer',
            'integration_engine',
            'server',
            'database',
            'storage',
            'network',
            'other',
        ]);

        return [
            'organization_id' => $organization->id,
            'site_id' => $site->id,
            'department_id' => $department->id,
            'name' => $this->systemName($type),
            'system_type' => $type,
            'status' => fake()->randomElement([
                'active',
                'planned',
                'maintenance',
                'inactive',
            ]),
            'hostname' => fake()->unique()->domainWord(),
            'fqdn' => fake()->unique()->domainName(),
            'ip_address' => fake()->unique()->ipv4(),
            'vendor' => fake()->randomElement([
                'VISUS',
                'Dedalus',
                'Siemens Healthineers',
                'GE HealthCare',
                'Canon Medical',
                'Philips',
                'Microsoft',
                'VMware',
                'Cisco',
            ]),
            'product' => fake()->word(),
            'model' => fake()->optional()->bothify('Model-###'),
            'version' => fake()->optional()->numerify('#.#.#'),
            'operating_system' => fake()->optional()->randomElement([
                'Windows Server',
                'Linux',
                'Embedded Linux',
                'VMware ESXi',
            ]),
            'operating_system_version' => fake()->optional()->numerify('##.#'),
            'serial_number' => fake()->optional()->bothify('SN-########'),
            'inventory_number' => fake()->optional()->bothify('INV-#####'),
            'description' => fake()->optional()->sentence(),
            'notes' => null,
            'archived_at' => null,
        ];
    }

    private function systemName(string $type): string
    {
        return match ($type) {
            'pacs' => 'PACS Produktion',
            'ris' => 'RIS Produktion',
            'kis' => 'KIS Produktion',
            'modality' => fake()->randomElement(['CT 1', 'MRT 1', 'Ultraschall 1']),
            'viewer' => 'Diagnostik-Viewer',
            'integration_engine' => 'HL7 Integration Engine',
            'server' => 'Applikationsserver',
            'database' => 'Datenbankserver',
            'storage' => 'Archiv-Storage',
            'network' => 'Core-Switch',
            default => 'Technisches System',
        };
    }
}
