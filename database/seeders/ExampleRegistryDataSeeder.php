<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DicomNode;
use App\Models\Organization;
use App\Models\Site;
use App\Models\System;
use Illuminate\Database\Seeder;

/**
 * Eindeutig fiktive Beispieldaten (Musterklinikum Nord) für lokale
 * Entwicklung und Demonstration. Wird ausschließlich in lokalen
 * Umgebungen ausgeführt (siehe DatabaseSeeder::run()).
 */
final class ExampleRegistryDataSeeder extends Seeder
{
    private const FICTIONAL_NOTE = 'Fiktive Beispieldaten für Demonstrations- und Testzwecke. Kein Bezug zu einer realen Einrichtung.';

    public function run(): void
    {
        $organization = Organization::query()->firstOrCreate(
            ['name' => 'Musterklinikum Nord'],
            ['description' => self::FICTIONAL_NOTE],
        );

        $mainSite = Site::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'name' => 'Hauptklinik'],
            ['country_code' => 'DE', 'timezone' => 'Europe/Berlin'],
        );

        Site::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'name' => 'Ambulantes Zentrum'],
            ['country_code' => 'DE', 'timezone' => 'Europe/Berlin'],
        );

        $radiologie = Department::query()->firstOrCreate(
            ['site_id' => $mainSite->id, 'name' => 'Radiologie'],
        );
        Department::query()->firstOrCreate(
            ['site_id' => $mainSite->id, 'name' => 'Kardiologie'],
        );
        Department::query()->firstOrCreate(
            ['site_id' => $mainSite->id, 'name' => 'Innere Medizin'],
        );

        $systems = [
            [
                'name' => 'PACS-ARCHIVE-01',
                'system_type' => 'pacs',
                'hostname' => 'pacs-archive-01',
                'ip_address' => '192.168.20.10',
                'node' => ['name' => 'PACS-ARCHIVE-01 Verification', 'ae_title' => 'PACS01', 'port' => 11112],
            ],
            [
                'name' => 'RIS-MWL-01',
                'system_type' => 'worklist_server',
                'hostname' => 'ris-mwl-01',
                'ip_address' => '192.168.20.20',
                'node' => ['name' => 'RIS-MWL-01 Worklist', 'ae_title' => 'MWL01', 'port' => 104],
            ],
            [
                'name' => 'CT-SOMATOM-01',
                'system_type' => 'ct',
                'hostname' => 'ct-somatom-01',
                'ip_address' => '192.168.20.31',
                'node' => ['name' => 'CT-SOMATOM-01 Verification', 'ae_title' => 'CT_RAD_01', 'port' => 104],
            ],
            [
                'name' => 'MR-MAGNETOM-01',
                'system_type' => 'mrt',
                'hostname' => 'mr-magnetom-01',
                'ip_address' => '192.168.20.32',
                'node' => ['name' => 'MR-MAGNETOM-01 Verification', 'ae_title' => 'MR_RAD_01', 'port' => 104],
            ],
            [
                'name' => 'US-VIEWPOINT-01',
                'system_type' => 'ultraschall',
                'hostname' => 'us-viewpoint-01',
                'ip_address' => '192.168.30.40',
                'node' => ['name' => 'US-VIEWPOINT-01 Verification', 'ae_title' => 'VIEWPOINT', 'port' => 104],
            ],
        ];

        foreach ($systems as $definition) {
            $system = System::query()->firstOrCreate(
                ['organization_id' => $organization->id, 'name' => $definition['name']],
                [
                    'site_id' => $mainSite->id,
                    'department_id' => $radiologie->id,
                    'system_type' => $definition['system_type'],
                    'status' => 'active',
                    'hostname' => $definition['hostname'],
                    'ip_address' => $definition['ip_address'],
                    'notes' => self::FICTIONAL_NOTE,
                ],
            );

            DicomNode::query()->firstOrCreate(
                ['system_id' => $system->id, 'ae_title' => $definition['node']['ae_title']],
                [
                    'name' => $definition['node']['name'],
                    'host' => $definition['hostname'],
                    'port' => $definition['node']['port'],
                    'role' => 'scp',
                    'status' => 'active',
                    'supports_echo' => true,
                ],
            );
        }
    }
}
