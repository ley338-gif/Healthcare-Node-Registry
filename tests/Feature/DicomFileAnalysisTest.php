<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\Diagnostics\DicomDumpResult;
use App\Services\Diagnostics\DicomDumpRunner;
use App\Services\Diagnostics\DicomFileAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class DicomFileAnalysisTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_file_is_analyzed_and_sensitive_values_are_masked(): void
    {
        $this->app->instance(DicomFileAnalyzer::class, new DicomFileAnalyzer(new FakeDumpRunner(new DicomDumpResult(true, 0, $this->validDump()))));
        $response = $this->actingAs($this->administrator())->post('/tests/dicom-file-analysis', ['dicom_file' => UploadedFile::fake()->createWithContent('image.dcm', str_repeat("\0", 128).'DICMdata')]);

        $response->assertSessionHas('success')->assertSessionHas('dicomFileAnalysis.successful', true)->assertSessionHas('dicomFileAnalysis.summary.patientId', '[REDACTED]');
        self::assertStringNotContainsString('REAL-PATIENT', session('dicomFileAnalysis.dump'));
        $this->assertDatabaseHas('security_events', ['event_type' => 'diagnostics.file-analysis.completed']);
    }

    public function test_invalid_file_returns_controlled_error_and_temporary_file_is_deleted(): void
    {
        $before = glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'hnr-analyze-*') ?: [];
        $this->app->instance(DicomFileAnalyzer::class, new DicomFileAnalyzer(new FakeDumpRunner(new DicomDumpResult(false, 1, 'invalid'))));
        $this->actingAs($this->administrator())->post('/tests/dicom-file-analysis', ['dicom_file' => UploadedFile::fake()->createWithContent('invalid.bin', 'not dicom')])->assertSessionHas('error');

        self::assertSame($before, glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'hnr-analyze-*') ?: []);
    }

    public function test_file_size_limit_is_enforced(): void
    {
        $this->actingAs($this->administrator())->post('/tests/dicom-file-analysis', ['dicom_file' => UploadedFile::fake()->create('large.dcm', 20481)])->assertSessionHasErrors('dicom_file');
    }

    public function test_permission_is_required(): void
    {
        $this->actingAs(User::factory()->create())->post('/tests/dicom-file-analysis', ['dicom_file' => UploadedFile::fake()->createWithContent('image.dcm', 'DICM')])->assertForbidden();
    }

    public function test_native_runner_analyzes_a_generated_dicom_file(): void
    {
        $directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'hnr-analysis-test-'.bin2hex(random_bytes(6));
        mkdir($directory, 0700);
        $dumpPath = $directory.DIRECTORY_SEPARATOR.'input.dump';
        $dicomPath = $directory.DIRECTORY_SEPARATOR.'input.dcm';
        try {
            file_put_contents($dumpPath, <<<'DUMP'
(0008,0016) UI [1.2.840.10008.5.1.4.1.1.7]
(0008,0018) UI [1.2.3.4.5]
(0010,0020) LO [TEST-PATIENT]
(0020,000d) UI [1.2.3]
(0020,000e) UI [1.2.3.4]
DUMP);
            $generator = new Process(['/usr/bin/dump2dcm', $dumpPath, $dicomPath]);
            $generator->mustRun();
            $result = (new DicomFileAnalyzer)->analyze(new UploadedFile($dicomPath, 'generated.dcm', null, null, true));

            self::assertTrue($result->successful);
            self::assertSame('1.2.840.10008.5.1.4.1.1.7', $result->summary['sopClassUid']);
            self::assertSame('[REDACTED]', $result->summary['patientId']);
        } finally {
            if (is_file($dicomPath)) {
                unlink($dicomPath);
            }
            if (is_file($dumpPath)) {
                unlink($dumpPath);
            }
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    private function administrator(): User
    {
        $this->seed();
        $user = User::factory()->create();
        $user->roles()->attach(Role::query()->where('name', 'system-administrator')->firstOrFail());

        return $user;
    }

    private function validDump(): string
    {
        return <<<'DUMP'
(0002,0002) UI [1.2.840.10008.5.1.4.1.1.2] MediaStorageSOPClassUID
(0002,0010) UI [1.2.840.10008.1.2.1] TransferSyntaxUID
(0008,0005) CS [ISO_IR 100] SpecificCharacterSet
(0008,0016) UI [1.2.840.10008.5.1.4.1.1.2] SOPClassUID
(0008,0018) UI [1.2.3.4.5] SOPInstanceUID
(0008,0050) SH [REAL-ACCESSION] AccessionNumber
(0010,0010) PN [REAL^PATIENT] PatientName
(0010,0020) LO [REAL-PATIENT] PatientID
(0020,000d) UI [1.2.3] StudyInstanceUID
(0020,000e) UI [1.2.3.4] SeriesInstanceUID
(7fe0,0010) OW 00\00 PixelData
DUMP;
    }
}

final readonly class FakeDumpRunner implements DicomDumpRunner
{
    public function __construct(private DicomDumpResult $result) {}

    public function run(string $path): DicomDumpResult
    {
        return $this->result;
    }
}
