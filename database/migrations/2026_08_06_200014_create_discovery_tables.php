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
        Schema::create('discovery_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('name');
            $table->string('location')->nullable();
            $table->string('department')->nullable();
            $table->string('ip_range'); // CIDR or range string
            $table->json('exclude_ips')->nullable();
            $table->string('status')->default('draft'); // draft, pending, running, cancelling, completed, failed, aborted
            $table->integer('progress_percentage')->default(0);
            $table->integer('total_ips')->default(0);
            $table->integer('processed_ips')->default(0);
            $table->integer('found_hosts_count')->default(0);
            $table->integer('dicom_candidates_count')->default(0);
            $table->json('scan_options')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('description')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('discovered_hosts', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('discovery_run_id')->constrained()->onDelete('cascade');
            $table->string('ip_address');
            $table->string('hostname')->nullable();
            $table->boolean('is_reachable')->default(false);
            $table->integer('ping_latency_ms')->nullable();
            $table->string('reverse_dns')->nullable();
            $table->string('status')->default('discovered'); // discovered, reviewing, confirmed, ignored
            $table->string('confidence_score')->default('unknown'); // very_low, low, medium, high, very_high
            $table->integer('confidence_percentage')->default(0);
            $table->string('suggested_system_type')->nullable();
            $table->foreignId('system_id')->nullable()->constrained('systems')->onDelete('set null');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['discovery_run_id', 'ip_address']);
        });

        Schema::create('discovered_ports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discovered_host_id')->constrained()->onDelete('cascade');
            $table->integer('port');
            $table->string('protocol')->default('tcp');
            $table->string('service_name')->nullable();
            $table->boolean('is_open')->default(false);
            $table->boolean('is_dicom_candidate')->default(false);
            $table->integer('response_time_ms')->nullable();
            $table->text('banner')->nullable();
            $table->timestamps();
        });

        Schema::create('dicom_discovery_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discovered_host_id')->constrained()->onDelete('cascade');
            $table->integer('port');
            $table->string('calling_ae');
            $table->string('called_ae');
            $table->boolean('association_successful')->default(false);
            $table->boolean('echo_successful')->default(false);
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->json('raw_response')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->timestamps();
        });

        Schema::create('discovery_classification_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discovered_host_id')->constrained()->onDelete('cascade');
            $table->string('rule_name');
            $table->string('reason');
            $table->integer('weight');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discovery_classification_evidence');
        Schema::dropIfExists('dicom_discovery_results');
        Schema::dropIfExists('discovered_ports');
        Schema::dropIfExists('discovered_hosts');
        Schema::dropIfExists('discovery_runs');
    }
};
