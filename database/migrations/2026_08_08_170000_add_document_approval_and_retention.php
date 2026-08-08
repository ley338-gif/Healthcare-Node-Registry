<?php

use App\Support\RegistryDocumentCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('permissions')->updateOrInsert(
            ['name' => 'documents.approve'],
            ['display_name' => 'Dokumentversionen freigeben', 'updated_at' => $now, 'created_at' => $now],
        );
        $permissionId = DB::table('permissions')->where('name', 'documents.approve')->value('id');
        $roleIds = DB::table('roles')->where('name', 'system-administrator')->pluck('id');
        foreach ($roleIds as $roleId) {
            DB::table('permission_role')->updateOrInsert(
                ['permission_id' => $permissionId, 'role_id' => $roleId],
                ['updated_at' => $now, 'created_at' => $now],
            );
        }

        Schema::table('registry_documents', function (Blueprint $table): void {
            $table->unsignedInteger('retention_policy_days')->nullable();
            $table->timestampTz('retention_until')->nullable()->index();
        });
        Schema::table('registry_document_versions', function (Blueprint $table): void {
            $table->string('approval_status', 40)->default('draft')->index();
            $table->foreignId('review_requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('review_requested_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
        });

        DB::table('registry_document_versions')->update(['approval_status' => 'approved']);
        foreach (RegistryDocumentCategory::cases() as $category) {
            $days = $category->retentionDays();
            DB::table('registry_documents')->where('category', $category->value)->update([
                'retention_policy_days' => $days,
                'retention_until' => DB::raw("created_at + interval '{$days} days'"),
            ]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('name', 'documents.approve')->value('id');
        if ($permissionId !== null) {
            DB::table('permission_role')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
        Schema::table('registry_document_versions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('review_requested_by');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['approval_status', 'review_requested_at', 'reviewed_at', 'review_note']);
        });
        Schema::table('registry_documents', function (Blueprint $table): void {
            $table->dropColumn(['retention_policy_days', 'retention_until']);
        });
    }
};
