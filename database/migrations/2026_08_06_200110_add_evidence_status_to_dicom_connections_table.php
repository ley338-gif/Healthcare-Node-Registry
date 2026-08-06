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
        Schema::table('dicom_connections', function (Blueprint $table): void {
            /*
             * Unabhängig vom Betriebsstatus (aktiv/geplant/wartung/inaktiv)
             * beschreibt evidence_status, wie belastbar die Verbindung
             * dokumentiert ist:
             *
             * confirmed            - vom Benutzer bestätigt
             * technically_tested   - technisch getestet, aber nicht als produktiv bestätigt
             * suspected            - vermutet (z. B. aus Discovery, nie automatisch bestätigt)
             * manually_documented  - manuell dokumentiert, ohne Test
             * failed_last_test     - letzter Test ist fehlgeschlagen
             */
            $table->string('evidence_status', 40)->default('manually_documented')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dicom_connections', function (Blueprint $table): void {
            $table->dropColumn('evidence_status');
        });
    }
};
