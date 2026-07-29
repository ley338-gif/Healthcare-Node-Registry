<?php

namespace Database\Factories;

use App\Models\DicomConnection;
use App\Models\DicomNode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DicomConnection>
 */
final class DicomConnectionFactory extends Factory
{
    protected $model = DicomConnection::class;

    public function definition(): array
    {
        return [
            'source_dicom_node_id' => DicomNode::factory(),
            'target_dicom_node_id' => DicomNode::factory(),
            'destination_dicom_node_id' => null,
            'name' => fake()->randomElement([
                'Bilderversand an PACS',
                'Modality Worklist',
                'PACS Query',
                'Bildabruf',
                'DICOM Verification',
            ]),
            'service' => fake()->randomElement(
                DicomConnection::SERVICES,
            ),
            'status' => 'active',
            'calling_ae_title' => null,
            'called_ae_title' => null,
            'port_override' => null,
            'tls_enabled' => false,
            'test_enabled' => true,
            'description' => null,
            'notes' => null,
            'archived_at' => null,
        ];
    }
}
