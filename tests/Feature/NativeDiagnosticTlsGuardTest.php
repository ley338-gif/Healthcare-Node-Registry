<?php

namespace Tests\Feature;

use App\Models\DicomNode;
use App\Services\Diagnostics\FindScuCommandExecutor;
use App\Services\Diagnostics\NativeCapabilityMatrixRunner;
use App\Services\Diagnostics\NativeGetCommandRunner;
use App\Services\Diagnostics\NativeMoveCommandRunner;
use App\Services\Diagnostics\NativeMppsCommandRunner;
use App\Services\Diagnostics\NativePacsQueryCommandRunner;
use App\Services\Diagnostics\NativeStorageCommandRunner;
use App\Services\Diagnostics\NativeStorageCommitmentCommandRunner;
use App\Services\Diagnostics\NativeWorklistCommandRunner;
use App\Services\Diagnostics\PacsQueryParameters;
use App\Services\Diagnostics\WorklistFindParameters;
use App\Services\Dicom\NativeDicomEchoCommandRunner;
use Tests\TestCase;

final class NativeDiagnosticTlsGuardTest extends TestCase
{
    private string $profileDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->profileDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'hnr-invalid-tls-'.bin2hex(random_bytes(8));
        $invalidProfileDirectory = $this->profileDirectory.DIRECTORY_SEPARATOR.'invalid-certificate';
        mkdir($invalidProfileDirectory, 0700, true);
        file_put_contents($invalidProfileDirectory.DIRECTORY_SEPARATOR.'private-key.pem', 'invalid');
        file_put_contents($invalidProfileDirectory.DIRECTORY_SEPARATOR.'certificate.pem', 'invalid');
        file_put_contents($invalidProfileDirectory.DIRECTORY_SEPARATOR.'ca.pem', 'invalid');
        config(['diagnostics.tls.profile_directory' => $this->profileDirectory]);
    }

    protected function tearDown(): void
    {
        $invalidProfileDirectory = $this->profileDirectory.DIRECTORY_SEPARATOR.'invalid-certificate';
        unlink($invalidProfileDirectory.DIRECTORY_SEPARATOR.'private-key.pem');
        unlink($invalidProfileDirectory.DIRECTORY_SEPARATOR.'certificate.pem');
        unlink($invalidProfileDirectory.DIRECTORY_SEPARATOR.'ca.pem');
        rmdir($invalidProfileDirectory);
        rmdir($this->profileDirectory);
        parent::tearDown();
    }

    public function test_echo_rejects_invalid_certificate_before_dcmtk_start(): void
    {
        $result = (new NativeDicomEchoCommandRunner)->run($this->node());

        self::assertFalse($result->successful);
        self::assertTrue($result->processError);
        self::assertStringContainsString('TLS-Profil', $result->output);
    }

    public function test_worklist_rejects_invalid_certificate_before_findscu_start(): void
    {
        $parameters = new WorklistFindParameters('HNR', 'PACS', null, '2026-08-08', null, null, null, null, null);
        $result = (new NativeWorklistCommandRunner(new FindScuCommandExecutor))->run($this->node(), $parameters);

        self::assertFalse($result->successful);
        self::assertTrue($result->processError);
        self::assertStringContainsString('TLS-Profil', $result->output);
    }

    public function test_pacs_query_rejects_invalid_certificate_before_findscu_start(): void
    {
        $parameters = new PacsQueryParameters('HNR', 'PACS', null, null, null, null, null, null, null, null);
        $result = (new NativePacsQueryCommandRunner(new FindScuCommandExecutor))->run($this->node(), $parameters);

        self::assertFalse($result->successful);
        self::assertTrue($result->processError);
        self::assertStringContainsString('TLS-Profil', $result->output);
    }

    public function test_storage_rejects_invalid_certificate_before_storescu_start(): void
    {
        $result = (new NativeStorageCommandRunner)->run($this->node(), 'HNR', 'PACS');

        self::assertFalse($result->successful);
        self::assertTrue($result->tlsConfigurationFailed);
        self::assertStringContainsString('TLS-Profil', $result->output);
    }

    public function test_move_rejects_invalid_certificate_before_movescu_start(): void
    {
        $result = (new NativeMoveCommandRunner)->run($this->node(), $this->plainNode(), 'HNR', 'PACS');

        self::assertFalse($result->successful);
        self::assertStringContainsString('TLS-Profil', $result->output);
    }

    public function test_get_rejects_invalid_certificate_before_getscu_start(): void
    {
        $result = (new NativeGetCommandRunner)->run($this->node(), 'HNR', 'PACS');

        self::assertFalse($result->successful);
        self::assertStringContainsString('TLS-Profil', $result->output);
    }

    public function test_mpps_rejects_invalid_certificate_before_python_start(): void
    {
        $result = (new NativeMppsCommandRunner)->run($this->node(), 'HNR', 'PACS');

        self::assertFalse($result->successful);
        self::assertSame('tls_configuration', $result->failureType);
    }

    public function test_storage_commitment_rejects_invalid_certificate_before_python_start(): void
    {
        $result = (new NativeStorageCommitmentCommandRunner)->run($this->node(), 'HNR', 'PACS', '1.2.3', '1.2.3.4');

        self::assertFalse($result->successful);
        self::assertSame('tls_configuration', $result->failureType);
    }

    public function test_capability_matrix_rejects_invalid_certificate_before_socket_connect(): void
    {
        $result = (new NativeCapabilityMatrixRunner)->run($this->node(), 'HNR', 'PACS', [
            ['id' => 1, 'sopClassUid' => '1.2.840.10008.1.1', 'transferSyntaxUid' => '1.2.840.10008.1.2'],
        ]);

        self::assertFalse($result->associationAccepted);
        self::assertStringContainsString('TLS-Profil', (string) $result->error);
    }

    private function node(): DicomNode
    {
        return new DicomNode([
            'host' => '127.0.0.1',
            'port' => 2762,
            'ae_title' => 'PACS',
            'tls_enabled' => true,
            'tls_profile' => 'invalid-certificate',
        ]);
    }

    private function plainNode(): DicomNode
    {
        return new DicomNode([
            'host' => '127.0.0.1',
            'port' => 104,
            'ae_title' => 'DESTINATION',
            'tls_enabled' => false,
        ]);
    }
}
