<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** @var array<string, string> */
    private const SYSTEM_TYPES = [
        'pacs' => 'PACS',
        'ris' => 'RIS',
        'kis' => 'KIS',
        'modality' => 'Modalität',
        'viewer' => 'Viewer',
        'integration_engine' => 'Integrationsserver',
        'server' => 'Server',
        'database' => 'Datenbank',
        'storage' => 'Storage',
        'network' => 'Netzwerkgerät',
        'other' => 'Sonstiges',
    ];

    /** @var array<string, string> */
    private const SYSTEM_STATUSES = [
        'active' => 'Aktiv',
        'planned' => 'Geplant',
        'maintenance' => 'Wartung',
        'inactive' => 'Inaktiv',
        'retired' => 'Außer Betrieb',
    ];

    public function up(): void
    {
        $this->createReferenceTable('system_types');
        $this->createReferenceTable('system_statuses');

        $this->insertDefaults('system_types', self::SYSTEM_TYPES, []);
        $this->insertDefaults('system_statuses', self::SYSTEM_STATUSES, ['active', 'retired']);
        $this->insertExistingValues('system_types', 'system_type');
        $this->insertExistingValues('system_statuses', 'status');

        Schema::table('systems', function (Blueprint $table): void {
            $table->foreign('system_type')->references('code')->on('system_types')->restrictOnDelete();
            $table->foreign('status')->references('code')->on('system_statuses')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('systems', function (Blueprint $table): void {
            $table->dropForeign(['system_type']);
            $table->dropForeign(['status']);
        });

        Schema::dropIfExists('system_statuses');
        Schema::dropIfExists('system_types');
    }

    private function createReferenceTable(string $tableName): void
    {
        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('code', 80)->unique();
            $table->string('label', 120);
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_protected')->default(false);
            $table->timestampsTz();
        });
    }

    /**
     * @param  array<string, string>  $values
     * @param  list<string>  $protected
     */
    private function insertDefaults(string $table, array $values, array $protected): void
    {
        $now = now();
        DB::table($table)->insert(array_map(
            static fn (string $label, string $code, int $index): array => [
                'public_id' => (string) Str::uuid7(),
                'code' => $code,
                'label' => $label,
                'sort_order' => ($index + 1) * 10,
                'is_active' => true,
                'is_protected' => in_array($code, $protected, true),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            array_values($values),
            array_keys($values),
            array_keys(array_values($values)),
        ));
    }

    private function insertExistingValues(string $table, string $column): void
    {
        $known = DB::table($table)->pluck('code');
        $now = now();
        $rows = DB::table('systems')
            ->whereNotNull($column)
            ->whereNotIn($column, $known)
            ->distinct()
            ->pluck($column)
            ->map(static fn (mixed $code): array => [
                'public_id' => (string) Str::uuid7(),
                'code' => (string) $code,
                'label' => Str::of((string) $code)->replace('_', ' ')->headline()->toString(),
                'sort_order' => 500,
                'is_active' => true,
                'is_protected' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        if ($rows !== []) {
            DB::table($table)->insert($rows);
        }
    }
};
