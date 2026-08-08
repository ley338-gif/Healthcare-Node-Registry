<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dicom_nodes', function (Blueprint $table): void {
            $table->string('modality', 16)->nullable()->after('ae_title');
        });
    }

    public function down(): void
    {
        Schema::table('dicom_nodes', function (Blueprint $table): void {
            $table->dropColumn('modality');
        });
    }
};
