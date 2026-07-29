<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'dicom_connections',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('public_id')->unique();

                /*
                 * Der Source-Knoten initiiert die DICOM-Association.
                 */
                $table->foreignId('source_dicom_node_id')
                    ->constrained('dicom_nodes')
                    ->restrictOnDelete();

                /*
                 * Der Target-Knoten nimmt die Association entgegen.
                 */
                $table->foreignId('target_dicom_node_id')
                    ->constrained('dicom_nodes')
                    ->restrictOnDelete();

                /*
                 * Bei C-MOVE kann das Ziel der Bilder ein dritter
                 * DICOM-Knoten sein.
                 */
                $table->foreignId('destination_dicom_node_id')
                    ->nullable()
                    ->constrained('dicom_nodes')
                    ->restrictOnDelete();

                $table->string('name', 160);

                /*
                 * Zunächst mit freiem DCMTK testbare Dienste:
                 *
                 * echo
                 * store
                 * worklist
                 * query
                 * move
                 * get
                 */
                $table->string('service', 40);

                $table->string('status', 40)->default('active');

                /*
                 * Optionale Overrides. Wenn null, werden die Werte
                 * des Source- beziehungsweise Target-Knotens benutzt.
                 */
                $table->string('calling_ae_title', 16)->nullable();
                $table->string('called_ae_title', 16)->nullable();
                $table->unsignedSmallInteger('port_override')->nullable();

                $table->boolean('tls_enabled')->default(false);
                $table->boolean('test_enabled')->default(true);

                $table->text('description')->nullable();
                $table->text('notes')->nullable();

                $table->timestampTz('archived_at')->nullable();
                $table->timestampsTz();

                $table->index([
                    'source_dicom_node_id',
                    'archived_at',
                ]);

                $table->index([
                    'target_dicom_node_id',
                    'archived_at',
                ]);

                $table->index([
                    'service',
                    'status',
                    'archived_at',
                ]);

                $table->unique(
                    [
                        'source_dicom_node_id',
                        'target_dicom_node_id',
                        'service',
                    ],
                    'dicom_connections_path_unique',
                );
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('dicom_connections');
    }
};
