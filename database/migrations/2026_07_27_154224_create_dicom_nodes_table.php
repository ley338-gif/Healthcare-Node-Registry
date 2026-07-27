<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dicom_nodes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();

            $table->foreignId('system_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('name', 160);
            $table->string('ae_title', 16);
            $table->string('host', 255);
            $table->unsignedSmallInteger('port');

            $table->string('role', 20)->default('both');
            $table->string('status', 40)->default('active');

            $table->boolean('tls_enabled')->default(false);

            $table->boolean('supports_echo')->default(true);
            $table->boolean('supports_store')->default(false);
            $table->boolean('supports_query')->default(false);
            $table->boolean('supports_retrieve')->default(false);
            $table->boolean('supports_storage_commitment')->default(false);
            $table->boolean('supports_mpps')->default(false);
            $table->boolean('supports_worklist')->default(false);

            $table->text('description')->nullable();
            $table->text('notes')->nullable();

            $table->timestampTz('last_verified_at')->nullable();
            $table->string('last_verification_status', 40)->nullable();
            $table->text('last_verification_message')->nullable();

            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();

            $table->index(['system_id', 'archived_at']);
            $table->index(['ae_title', 'archived_at']);
            $table->index(['host', 'port']);
            $table->index(['status', 'archived_at']);

            $table->unique(
                ['system_id', 'ae_title', 'host', 'port'],
                'dicom_nodes_endpoint_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dicom_nodes');
    }
};
