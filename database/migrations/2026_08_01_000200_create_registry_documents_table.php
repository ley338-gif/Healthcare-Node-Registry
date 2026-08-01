<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registry_documents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('documentable_type', 120);
            $table->unsignedBigInteger('documentable_id');
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->string('title', 200);
            $table->text('description')->nullable();
            $table->string('category', 80);
            $table->string('visibility', 40)->default('internal');
            $table->string('status', 40)->default('active');
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('contract_reference', 200)->nullable();
            $table->jsonb('tags')->default('[]');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->constrained('users');
            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();

            $table->index(['documentable_type', 'documentable_id'], 'registry_documents_documentable_index');
            $table->index(['documentable_type', 'documentable_id', 'archived_at'], 'registry_documents_context_active_index');
            $table->index(['category', 'status']);
            $table->index('valid_until');
        });

        Schema::create('registry_document_versions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('registry_document_id')->constrained('registry_documents');
            $table->unsignedInteger('version_number');
            $table->string('original_filename', 255);
            $table->string('stored_filename', 255);
            $table->string('storage_disk', 80);
            $table->string('storage_path', 1000);
            $table->string('mime_type', 160);
            $table->string('file_extension', 20);
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestampTz('uploaded_at');
            $table->text('change_note')->nullable();
            $table->string('malware_scan_status', 40)->default('pending');
            $table->text('malware_scan_message')->nullable();
            $table->jsonb('metadata')->default('{}');
            $table->timestampsTz();

            $table->unique(['registry_document_id', 'version_number'], 'registry_document_versions_number_unique');
            $table->unique(['id', 'registry_document_id'], 'registry_document_versions_document_owner_unique');
            $table->index(['registry_document_id', 'malware_scan_status'], 'registry_document_versions_scan_index');
            $table->index('sha256');
        });

        Schema::table('registry_documents', function (Blueprint $table): void {
            $table->foreign(['current_version_id', 'id'], 'registry_documents_current_version_owner_foreign')
                ->references(['id', 'registry_document_id'])
                ->on('registry_document_versions')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('registry_documents', function (Blueprint $table): void {
            $table->dropForeign('registry_documents_current_version_owner_foreign');
        });
        Schema::dropIfExists('registry_document_versions');
        Schema::dropIfExists('registry_documents');
    }
};
