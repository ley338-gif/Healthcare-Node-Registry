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
        Schema::create('system_network_interfaces', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('system_id')->constrained()->cascadeOnDelete();
            $table->string('interface_label', 160);
            $table->string('hostname', 255)->nullable();
            $table->string('fqdn', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestampsTz();

            $table->unique(['system_id', 'interface_label']);
            $table->index('hostname');
            $table->index('fqdn');
            $table->index('ip_address');
        });

        DB::statement('CREATE UNIQUE INDEX system_network_interfaces_one_primary ON system_network_interfaces (system_id) WHERE is_primary = true');

        $now = now();
        DB::table('systems')
            ->where(fn ($query) => $query->whereNotNull('hostname')->orWhereNotNull('fqdn')->orWhereNotNull('ip_address'))
            ->orderBy('id')
            ->each(function (object $system) use ($now): void {
                DB::table('system_network_interfaces')->insert([
                    'public_id' => (string) Str::uuid7(),
                    'system_id' => $system->id,
                    'interface_label' => 'Primärschnittstelle',
                    'hostname' => $system->hostname,
                    'fqdn' => $system->fqdn,
                    'ip_address' => $system->ip_address,
                    'is_primary' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_network_interfaces');
    }
};
