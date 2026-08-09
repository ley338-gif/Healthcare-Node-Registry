<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discovery_schedules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('name');
            $table->string('frequency');
            $table->time('scheduled_time');
            $table->unsignedTinyInteger('day_of_week')->nullable();
            $table->string('timezone', 64);
            $table->json('configuration');
            $table->boolean('active')->default(true);
            $table->timestamp('next_run_at')->index();
            $table->timestamp('last_dispatched_at')->nullable();
            $table->text('last_error')->nullable();
            $table->foreignId('last_run_id')->nullable()->constrained('discovery_runs')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('discovery_runs', function (Blueprint $table): void {
            $table->foreignId('discovery_schedule_id')->nullable()->after('created_by')->constrained('discovery_schedules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('discovery_runs', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('discovery_schedule_id');
        });
        Schema::dropIfExists('discovery_schedules');
    }
};
