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
        Schema::table('systems', function (Blueprint $table): void {
            $table->string('responsible')->nullable()->after('notes');
            $table->string('criticality')->nullable()->after('responsible'); // low, medium, high, critical
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('systems', function (Blueprint $table): void {
            $table->dropColumn(['responsible', 'criticality']);
        });
    }
};
