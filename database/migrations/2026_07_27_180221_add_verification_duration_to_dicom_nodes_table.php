<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dicom_nodes', function (Blueprint $table): void {
            $table
                ->unsignedInteger('last_verification_duration_ms')
                ->nullable()
                ->after('last_verification_status');
        });
    }

    public function down(): void
    {
        Schema::table('dicom_nodes', function (Blueprint $table): void {
            $table->dropColumn('last_verification_duration_ms');
        });
    }
};
