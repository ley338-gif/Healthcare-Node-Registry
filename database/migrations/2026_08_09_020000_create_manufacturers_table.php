<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manufacturers', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('code', 160)->unique();
            $table->string('label', 160);
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_protected')->default(false);
            $table->timestampsTz();
        });

        $now = now();
        $manufacturers = DB::table('systems')
            ->whereNotNull('vendor')
            ->where('vendor', '!=', '')
            ->distinct()
            ->orderBy('vendor')
            ->pluck('vendor')
            ->map(static fn (mixed $vendor, int $index): array => [
                'public_id' => (string) Str::uuid7(),
                'code' => (string) $vendor,
                'label' => (string) $vendor,
                'sort_order' => min(($index + 1) * 10, 65535),
                'is_active' => true,
                'is_protected' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($manufacturers !== []) {
            DB::table('manufacturers')->insert($manufacturers);
        }

        DB::table('systems')->where('vendor', '')->update(['vendor' => null]);

        Schema::table('systems', function (Blueprint $table): void {
            $table->foreign('vendor')->references('code')->on('manufacturers')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('systems', function (Blueprint $table): void {
            $table->dropForeign(['vendor']);
        });

        Schema::dropIfExists('manufacturers');
    }
};
