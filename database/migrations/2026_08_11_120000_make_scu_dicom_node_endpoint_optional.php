<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dicom_nodes', function (Blueprint $table): void {
            $table->string('host', 255)->nullable()->change();
            $table->unsignedSmallInteger('port')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('dicom_nodes')
            ->whereNull('host')
            ->update(['host' => 'unknown.invalid']);

        DB::table('dicom_nodes')
            ->whereNull('port')
            ->update(['port' => 11112]);

        Schema::table('dicom_nodes', function (Blueprint $table): void {
            $table->string('host', 255)->nullable(false)->change();
            $table->unsignedSmallInteger('port')->nullable(false)->change();
        });
    }
};
