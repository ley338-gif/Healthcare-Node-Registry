<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table): void {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->string('event_type', 160)->index();
            $table->string('actor_type', 80);
            $table->string('subject_type', 120)->nullable();
            $table->uuid('subject_public_id')->nullable()->index();
            $table->jsonb('metadata')->default('{}');
            $table->timestampTz('occurred_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
