<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('discovery_exclusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discovery_run_id')->constrained()->onDelete('cascade');
            $table->string('ip_address');
            $table->timestamps();

            $table->unique(['discovery_run_id', 'ip_address']);
        });

        Schema::create('discovery_ports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discovery_run_id')->constrained()->onDelete('cascade');
            $table->integer('port');
            $table->string('protocol')->default('tcp');
            $table->string('label')->nullable();
            $table->boolean('is_dicom_candidate')->default(false);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['discovery_run_id', 'port', 'protocol']);
        });

        Schema::create('discovery_ae_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discovery_run_id')->constrained()->onDelete('cascade');
            $table->string('ae_title');
            $table->string('role'); // calling, called
            $table->string('source'); // manual, imported, registry, hostname_derived, default
            $table->timestamps();
        });

        Schema::create('discovery_allowed_networks', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('cidr');
            $table->string('description')->nullable();
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['cidr']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discovery_allowed_networks');
        Schema::dropIfExists('discovery_ae_candidates');
        Schema::dropIfExists('discovery_ports');
        Schema::dropIfExists('discovery_exclusions');
    }
};
