<?php

namespace Database\Factories;

use App\Models\RegistryDocument;
use App\Models\RegistryDocumentVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RegistryDocumentVersion> */
final class RegistryDocumentVersionFactory extends Factory
{
    protected $model = RegistryDocumentVersion::class;

    public function definition(): array
    {
        $contents = fake()->text(200);

        return [
            'registry_document_id' => RegistryDocument::factory(),
            'version_number' => 1,
            'original_filename' => 'document.pdf',
            'stored_filename' => fake()->uuid().'.pdf',
            'storage_disk' => 'local',
            'storage_path' => 'registry-documents/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'file_extension' => 'pdf',
            'size_bytes' => strlen($contents),
            'sha256' => hash('sha256', $contents),
            'uploaded_by' => User::factory(),
            'uploaded_at' => now(),
            'change_note' => null,
            'malware_scan_status' => 'pending',
            'malware_scan_message' => null,
            'metadata' => [],
        ];
    }
}
