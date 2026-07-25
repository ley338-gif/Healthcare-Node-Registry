<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('name', 200)->unique();
            $table->string('short_name', 40)->nullable();
            $table->text('description')->nullable();
            $table->timestampTz('archived_at')->nullable()->index();
            $table->timestampsTz();
        });
        Schema::create('sites', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('name', 200);
            $table->string('code', 40)->nullable();
            $table->string('street', 200)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('city', 120)->nullable();
            $table->char('country_code', 2)->default('DE');
            $table->string('timezone', 80)->default('Europe/Berlin');
            $table->text('description')->nullable();
            $table->timestampTz('archived_at')->nullable()->index();
            $table->timestampsTz();
            $table->unique(['organization_id', 'name']);
        });
        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('site_id')->constrained()->restrictOnDelete();
            $table->string('name', 200);
            $table->string('code', 40)->nullable();
            $table->string('specialty', 120)->nullable();
            $table->text('description')->nullable();
            $table->timestampTz('archived_at')->nullable()->index();
            $table->timestampsTz();
            $table->unique(['site_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('departments');
        Schema::dropIfExists('sites');
        Schema::dropIfExists('organizations');
    }
};
