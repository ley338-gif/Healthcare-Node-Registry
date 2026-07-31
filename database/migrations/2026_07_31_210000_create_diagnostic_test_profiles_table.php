<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_test_profiles', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('name', 160);
            $table->text('description')->nullable();
            $table->string('test_type', 60);
            $table->foreignId('dicom_node_id')->constrained()->restrictOnDelete();
            $table->string('calling_ae_title', 16)->nullable();
            $table->jsonb('configuration')->default('{}');
            $table->unsignedSmallInteger('timeout_seconds')->default(15);
            $table->boolean('enabled')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();

            $table->index(['test_type', 'enabled', 'archived_at']);
            $table->index(['dicom_node_id', 'enabled']);
            $table->index(['created_by', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_test_profiles');
    }
};
