<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'dicom_node_verifications',
            function (Blueprint $table): void {
                $table->id();
                $table->uuid('public_id')->unique();

                $table->foreignId('dicom_node_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('triggered_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->string('status', 40);
                $table->boolean('successful');
                $table->unsignedInteger('duration_ms');
                $table->integer('exit_code');
                $table->text('message')->nullable();
                $table->timestampTz('verified_at');
                $table->timestampsTz();

                $table->index([
                    'dicom_node_id',
                    'verified_at',
                ]);

                $table->index([
                    'status',
                    'verified_at',
                ]);
            },
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('dicom_node_verifications');
    }
};
