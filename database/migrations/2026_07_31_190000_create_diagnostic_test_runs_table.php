<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diagnostic_test_runs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dicom_node_id')->constrained()->restrictOnDelete();
            $table->foreignId('system_id')->constrained()->restrictOnDelete();
            $table->string('test_type', 60);
            $table->string('status', 30);
            $table->timestampTz('started_at');
            $table->timestampTz('finished_at');
            $table->unsignedInteger('duration_ms');
            $table->unsignedInteger('result_count')->nullable();
            $table->string('target_host', 255);
            $table->unsignedSmallInteger('target_port');
            $table->string('calling_ae_title', 16)->nullable();
            $table->string('called_ae_title', 16)->nullable();
            $table->text('summary');
            $table->jsonb('steps')->default('[]');
            $table->jsonb('details')->default('{}');
            $table->jsonb('warnings')->default('[]');
            $table->jsonb('errors')->default('[]');
            $table->text('sanitized_log_excerpt')->nullable();
            $table->timestampsTz();

            $table->index(['dicom_node_id', 'created_at']);
            $table->index(['system_id', 'created_at']);
            $table->index(['test_type', 'status']);
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diagnostic_test_runs');
    }
};
