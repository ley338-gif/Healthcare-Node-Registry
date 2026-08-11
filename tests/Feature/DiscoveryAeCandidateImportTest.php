<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;
use ZipArchive;

final class DiscoveryAeCandidateImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_candidates_are_previewed_without_starting_a_run(): void
    {
        $csv = "schema_version;ae_title;role\n1;pacs_main;called\n1;hnr_scan;calling\n1;PACS_MAIN;called";

        $response = $this->actingAs($this->createRunner())->from('/discovery/runs/create')->post(
            '/discovery/ae-candidates/import-preview',
            ['ae_candidate_file' => UploadedFile::fake()->createWithContent('candidates.csv', $csv)],
        );

        $response->assertRedirect('/discovery/runs/create')
            ->assertSessionHas('discovery_ae_import_preview.format', 'csv')
            ->assertSessionHas('discovery_ae_import_preview.rows_processed', 3)
            ->assertSessionHas('discovery_ae_import_preview.candidates.0.ae_title', 'PACS_MAIN')
            ->assertSessionHas('discovery_ae_import_preview.candidates.1.role', 'calling')
            ->assertSessionHas('discovery_ae_import_preview.warnings.0');
        $this->assertDatabaseCount('discovery_runs', 0);
    }

    public function test_invalid_rows_are_returned_with_line_numbers(): void
    {
        $csv = "schema_version,ae_title,role\n2,TOO_LONG_AE_TITLE_123,unknown";

        $this->actingAs($this->createRunner())->post('/discovery/ae-candidates/import-preview', [
            'ae_candidate_file' => UploadedFile::fake()->createWithContent('candidates.csv', $csv),
        ])->assertSessionHas('discovery_ae_import_preview.errors', function (array $errors): bool {
            return count($errors) === 3 && collect($errors)->every(fn (array $error): bool => $error['line'] === 2);
        });
    }

    public function test_xlsx_candidates_are_previewed_without_evaluating_formulas(): void
    {
        $response = $this->actingAs($this->createRunner())->post('/discovery/ae-candidates/import-preview', [
            'ae_candidate_file' => $this->xlsx([
                ['schema_version', 'ae_title', 'role'],
                ['1', 'RIS_TEST', 'called'],
            ]),
        ]);

        $response->assertSessionHas('discovery_ae_import_preview.format', 'xlsx')
            ->assertSessionHas('discovery_ae_import_preview.candidates.0.ae_title', 'RIS_TEST');

        $this->actingAs($this->createRunner())->post('/discovery/ae-candidates/import-preview', [
            'ae_candidate_file' => $this->xlsx([
                ['schema_version', 'ae_title', 'role'],
                ['1', '=DANGEROUS()', 'called'],
            ], formulaCell: 'B2'),
        ])->assertSessionHasErrors('ae_candidate_file');
    }

    public function test_import_requires_discovery_run_permission_and_supported_extension(): void
    {
        $file = UploadedFile::fake()->createWithContent('candidates.csv', "schema_version,ae_title\n1,PACS");
        $this->actingAs(User::factory()->create())
            ->post('/discovery/ae-candidates/import-preview', ['ae_candidate_file' => $file])
            ->assertForbidden();

        $this->actingAs($this->createRunner())->post('/discovery/ae-candidates/import-preview', [
            'ae_candidate_file' => UploadedFile::fake()->createWithContent('candidates.txt', 'not supported'),
        ])->assertSessionHasErrors('ae_candidate_file');
    }

    /** @param list<list<string>> $rows */
    private function xlsx(array $rows, ?string $formulaCell = null): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'discovery-xlsx-');
        self::assertIsString($path);
        $archive = new ZipArchive;
        self::assertTrue($archive->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE));
        $archive->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Candidates" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $archive->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $sheetRows = '';
        foreach ($rows as $rowIndex => $row) {
            $cells = '';
            foreach ($row as $columnIndex => $value) {
                $reference = chr(65 + $columnIndex).($rowIndex + 1);
                $escaped = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $cells .= $reference === $formulaCell
                    ? "<c r=\"{$reference}\"><f>{$escaped}</f><v>0</v></c>"
                    : "<c r=\"{$reference}\" t=\"inlineStr\"><is><t>{$escaped}</t></is></c>";
            }
            $sheetRows .= '<row r="'.($rowIndex + 1).'">'.$cells.'</row>';
        }
        $archive->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$sheetRows.'</sheetData></worksheet>');
        $archive->close();

        return new UploadedFile($path, 'candidates.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function createRunner(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $permission = Permission::query()->where('name', 'discovery.run')->firstOrFail();
        $role = Role::query()->create(['name' => 'discovery-importer-'.str()->uuid(), 'display_name' => 'Discovery Importer']);
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        return $user;
    }
}
