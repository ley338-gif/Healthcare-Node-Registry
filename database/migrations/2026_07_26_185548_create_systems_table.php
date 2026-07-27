<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('systems', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();

            $table->foreignId('organization_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('site_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('department_id')
                ->nullable()
                ->constrained()
                ->restrictOnDelete();

            $table->string('name', 160);
            $table->string('system_type', 80);
            $table->string('status', 40)->default('active');

            $table->string('hostname', 255)->nullable();
            $table->string('fqdn', 255)->nullable();
            $table->string('ip_address', 45)->nullable();

            $table->string('vendor', 160)->nullable();
            $table->string('product', 160)->nullable();
            $table->string('model', 160)->nullable();
            $table->string('version', 120)->nullable();

            $table->string('operating_system', 160)->nullable();
            $table->string('operating_system_version', 120)->nullable();

            $table->string('serial_number', 160)->nullable();
            $table->string('inventory_number', 160)->nullable();

            $table->text('description')->nullable();
            $table->text('notes')->nullable();

            $table->timestampTz('archived_at')->nullable();
            $table->timestampsTz();

            $table->index(['organization_id', 'archived_at']);
            $table->index(['site_id', 'archived_at']);
            $table->index(['department_id', 'archived_at']);
            $table->index(['system_type', 'archived_at']);
            $table->index(['status', 'archived_at']);
            $table->index('hostname');
            $table->index('ip_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('systems');
    }
};
