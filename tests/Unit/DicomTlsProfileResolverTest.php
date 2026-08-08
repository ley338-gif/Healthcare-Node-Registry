<?php

namespace Tests\Unit;

use App\Models\DicomNode;
use App\Services\Dicom\DicomTlsConfigurationException;
use App\Services\Dicom\DicomTlsProfileResolver;
use Tests\TestCase;

final class DicomTlsProfileResolverTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'hnr-tls-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700, true);
        config([
            'diagnostics.tls.profile_directory' => $this->directory,
            'diagnostics.tls.private_key_filename' => 'private-key.pem',
            'diagnostics.tls.certificate_filename' => 'certificate.pem',
            'diagnostics.tls.ca_certificate_filename' => 'ca.pem',
        ]);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);
        parent::tearDown();
    }

    public function test_plain_node_needs_no_tls_profile(): void
    {
        self::assertNull((new DicomTlsProfileResolver)->resolve($this->node(false, null)));
    }

    public function test_enabled_tls_profile_resolves_verified_dcmtk_and_python_configuration(): void
    {
        $this->createProfile('pacs-production');

        $profile = (new DicomTlsProfileResolver)->resolve($this->node(true, 'pacs-production'));

        self::assertNotNull($profile);
        self::assertSame('pacs-production', $profile->reference);
        self::assertSame(
            ['+tls', $profile->privateKeyPath, $profile->certificatePath, '-pw', '+cf', $profile->caCertificatePath, '-rc', '+py'],
            $profile->dcmtkArguments(),
        );
        self::assertSame('pacs-production', $profile->pythonPayload()['profile']);
        self::assertTrue($profile->pythonPayload()['enabled']);
        self::assertIsResource($profile->clientStreamContext());
    }

    public function test_enabled_tls_never_falls_back_when_profile_is_missing(): void
    {
        $this->expectException(DicomTlsConfigurationException::class);
        $this->expectExceptionMessage("DICOM-TLS-Profil 'missing' ist nicht verfügbar");

        (new DicomTlsProfileResolver)->resolve($this->node(true, 'missing'));
    }

    public function test_enabled_tls_never_falls_back_when_certificate_is_invalid(): void
    {
        $profileDirectory = $this->directory.DIRECTORY_SEPARATOR.'invalid-certificate';
        mkdir($profileDirectory, 0700, true);
        file_put_contents($profileDirectory.DIRECTORY_SEPARATOR.'private-key.pem', 'invalid');
        file_put_contents($profileDirectory.DIRECTORY_SEPARATOR.'certificate.pem', 'invalid');
        file_put_contents($profileDirectory.DIRECTORY_SEPARATOR.'ca.pem', 'invalid');

        $this->expectException(DicomTlsConfigurationException::class);
        $this->expectExceptionMessage('Client-Zertifikat des DICOM-TLS-Profils');

        (new DicomTlsProfileResolver)->resolve($this->node(true, 'invalid-certificate'));
    }

    private function node(bool $enabled, ?string $profile): DicomNode
    {
        return new DicomNode([
            'host' => 'pacs.example.test',
            'port' => 2762,
            'ae_title' => 'PACS',
            'tls_enabled' => $enabled,
            'tls_profile' => $profile,
        ]);
    }

    private function createProfile(string $reference): void
    {
        $profileDirectory = $this->directory.DIRECTORY_SEPARATOR.$reference;
        mkdir($profileDirectory, 0700, true);

        $caKey = openssl_pkey_new(['private_key_bits' => 2048]);
        self::assertNotFalse($caKey);
        $caRequest = openssl_csr_new(['commonName' => 'HNR Test CA'], $caKey, ['digest_alg' => 'sha256']);
        self::assertNotFalse($caRequest);
        $caCertificate = openssl_csr_sign($caRequest, null, $caKey, 30, ['digest_alg' => 'sha256']);
        self::assertNotFalse($caCertificate);

        $clientKey = openssl_pkey_new(['private_key_bits' => 2048]);
        self::assertNotFalse($clientKey);
        $clientRequest = openssl_csr_new(['commonName' => 'HNR Diagnostic Client'], $clientKey, ['digest_alg' => 'sha256']);
        self::assertNotFalse($clientRequest);
        $clientCertificate = openssl_csr_sign($clientRequest, $caCertificate, $caKey, 30, ['digest_alg' => 'sha256']);
        self::assertNotFalse($clientCertificate);

        self::assertTrue(openssl_pkey_export($clientKey, $privateKeyPem, null));
        self::assertTrue(openssl_x509_export($clientCertificate, $certificatePem));
        self::assertTrue(openssl_x509_export($caCertificate, $caCertificatePem));
        file_put_contents($profileDirectory.DIRECTORY_SEPARATOR.'private-key.pem', $privateKeyPem);
        file_put_contents($profileDirectory.DIRECTORY_SEPARATOR.'certificate.pem', $certificatePem);
        file_put_contents($profileDirectory.DIRECTORY_SEPARATOR.'ca.pem', $caCertificatePem);
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
