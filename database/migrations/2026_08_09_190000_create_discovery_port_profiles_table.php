<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discovery_port_profiles', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('name', 160);
            $table->string('description')->nullable();
            $table->json('ports');
            $table->boolean('active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('name');
        });

        $dicomLabels = [
            104 => 'DICOM Standard (Well-Known Port)',
            11112 => 'DICOM Standard (registriert)',
            11113 => 'DICOM Storage Commitment (Callback)',
            2761 => 'DICOM TLS',
            2762 => 'DICOM ISCL',
            4242 => 'DICOM (herstellerspezifisch verbreitet)',
            5678 => 'DICOM (herstellerspezifisch verbreitet)',
            7104 => 'DICOM (herstellerspezifisch verbreitet)',
            8000 => 'DICOM über HTTP-typischen Port',
            8042 => 'Orthanc REST/DICOMweb (verbreitet)',
            8104 => 'DICOM (herstellerspezifisch verbreitet)',
        ];
        $infrastructureLabels = [
            80 => 'HTTP', 443 => 'HTTPS', 389 => 'LDAP', 636 => 'LDAPS',
            1433 => 'Microsoft SQL Server', 1521 => 'Oracle DB',
            3306 => 'MySQL/MariaDB', 5432 => 'PostgreSQL',
        ];

        $ports = collect($dicomLabels)->map(fn (string $label, int $port): array => [
            'port' => $port, 'protocol' => 'tcp', 'label' => $label,
            'is_dicom_candidate' => true, 'enabled' => true,
        ])->merge(collect($infrastructureLabels)->map(fn (string $label, int $port): array => [
            'port' => $port, 'protocol' => 'tcp', 'label' => $label,
            'is_dicom_candidate' => false, 'enabled' => false,
        ]))->values()->all();

        DB::table('discovery_port_profiles')->insert([
            'public_id' => (string) Str::uuid7(),
            'name' => 'DICOM Standard',
            'description' => 'Bestehende DICOM-Kandidaten sowie optional aktivierbare Infrastruktur-Ports.',
            'ports' => json_encode($ports, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'active' => true,
            'is_default' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('discovery_port_profiles');
    }
};
