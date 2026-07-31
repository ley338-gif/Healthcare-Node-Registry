<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registry_documentation', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('documentable_type', 120);
            $table->unsignedBigInteger('documentable_id');
            $table->string('documentation_type', 80)->default('operations');
            $table->string('section', 120);
            $table->string('title', 200);
            $table->text('content')->nullable();
            $table->jsonb('structured_data')->default('{}');
            $table->string('visibility', 40)->default('internal');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestampsTz();

            $table->index(['documentable_type', 'documentable_id'], 'registry_documentation_documentable_index');
            $table->unique(
                ['documentable_type', 'documentable_id', 'documentation_type', 'section'],
                'registry_documentation_section_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registry_documentation');
    }
};
