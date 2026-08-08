<?php

namespace Tests\Feature;

use App\Models\DicomNode;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

final class SystemOverviewExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_excel_export_contains_system_and_dicom_node_and_respects_filters(): void
    {
        $included = System::factory()->create(['name' => 'PACS ÄÖÜ', 'vendor' => 'Example Medical']);
        $node = DicomNode::factory()->create([
            'system_id' => $included->id,
            'name' => 'PACS Archiv',
            'ae_title' => 'PACS_ARCHIVE',
            'host' => '10.50.1.20',
            'port' => 11112,
        ]);
        System::factory()->create(['name' => 'Nicht enthalten']);

        $response = $this->actingAs($this->administrator())->get('/systems/export/xlsx?organization='.$included->organization_id);
        $response->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        self::assertStringStartsWith('PK', $response->getContent());

        $sheet = $this->sheetXml($response->getContent());
        self::assertStringContainsString('PACS ÄÖÜ', $sheet);
        self::assertStringContainsString($node->ae_title, $sheet);
        self::assertStringContainsString('10.50.1.20', $sheet);
        self::assertStringNotContainsString('Nicht enthalten', $sheet);
    }

    public function test_pdf_export_contains_filtered_system_overview(): void
    {
        $system = System::factory()->create(['name' => 'RIS Produktion', 'system_type' => 'ris']);
        DicomNode::factory()->create(['system_id' => $system->id, 'ae_title' => 'RIS_PROD']);

        $response = $this->actingAs($this->administrator())->get('/systems/export/pdf?type=ris');
        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        self::assertStringStartsWith('%PDF-1.4', $response->getContent());
        self::assertStringContainsString('System- und Knotenuebersicht', $response->getContent());
        self::assertStringContainsString('RIS Produktion', $response->getContent());
    }

    public function test_export_requires_registry_view_permission(): void
    {
        $this->actingAs(User::factory()->create())->get('/systems/export/xlsx')->assertForbidden();
    }

    private function administrator(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());

        return $user;
    }

    private function sheetXml(string $xlsx): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx-test-');
        self::assertNotFalse($path);
        file_put_contents($path, $xlsx);
        $zip = new ZipArchive;
        self::assertTrue($zip->open($path));
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        unlink($path);
        self::assertIsString($sheet);

        return $sheet;
    }
}
