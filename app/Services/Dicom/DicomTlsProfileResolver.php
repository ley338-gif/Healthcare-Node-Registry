<?php

namespace App\Services\Dicom;

use App\Models\DicomNode;
use OpenSSLAsymmetricKey;
use OpenSSLCertificate;

final class DicomTlsProfileResolver
{
    public function resolve(DicomNode $node): ?DicomTlsProfile
    {
        if (! $node->tls_enabled) {
            return null;
        }

        $reference = trim((string) $node->tls_profile);
        if ($reference === '' || preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]{0,63}\z/', $reference) !== 1) {
            throw new DicomTlsConfigurationException('DICOM-TLS ist aktiviert, aber es ist kein gültiges TLS-Profil referenziert.');
        }

        $root = realpath((string) config('diagnostics.tls.profile_directory'));
        if ($root === false || ! is_dir($root)) {
            throw new DicomTlsConfigurationException('Das konfigurierte DICOM-TLS-Profilverzeichnis ist nicht verfügbar.');
        }

        $profileDirectory = realpath($root.DIRECTORY_SEPARATOR.$reference);
        if ($profileDirectory === false || ! is_dir($profileDirectory) || ! $this->isWithin($profileDirectory, $root)) {
            throw new DicomTlsConfigurationException("Das DICOM-TLS-Profil '{$reference}' ist nicht verfügbar.");
        }

        $privateKeyPath = $this->profileFile($profileDirectory, $root, 'private_key_filename', 'privater Schlüssel');
        $certificatePath = $this->profileFile($profileDirectory, $root, 'certificate_filename', 'Client-Zertifikat');
        $caCertificatePath = $this->profileFile($profileDirectory, $root, 'ca_certificate_filename', 'CA-Zertifikat');

        $certificate = $this->certificate($certificatePath, 'Client-Zertifikat');
        $caCertificate = $this->certificate($caCertificatePath, 'CA-Zertifikat');
        $privateKey = $this->privateKey($privateKeyPath);
        $this->assertCertificateIsCurrent($certificate);

        if (! openssl_x509_check_private_key($certificate, $privateKey)) {
            throw new DicomTlsConfigurationException("Client-Zertifikat und privater Schlüssel des DICOM-TLS-Profils '{$reference}' passen nicht zusammen.");
        }

        unset($caCertificate);

        return new DicomTlsProfile($reference, $privateKeyPath, $certificatePath, $caCertificatePath);
    }

    private function profileFile(string $profileDirectory, string $root, string $configKey, string $label): string
    {
        $filename = (string) config("diagnostics.tls.{$configKey}");
        $path = realpath($profileDirectory.DIRECTORY_SEPARATOR.$filename);
        if ($path === false || ! is_file($path) || ! is_readable($path) || ! $this->isWithin($path, $root)) {
            throw new DicomTlsConfigurationException("Der {$label} des DICOM-TLS-Profils fehlt oder ist nicht lesbar.");
        }

        return $path;
    }

    private function certificate(string $path, string $label): OpenSSLCertificate
    {
        $contents = file_get_contents($path);
        $certificate = $contents === false ? false : @openssl_x509_read($contents);
        if ($certificate === false) {
            throw new DicomTlsConfigurationException("Der {$label} des DICOM-TLS-Profils ist ungültig.");
        }

        return $certificate;
    }

    private function privateKey(string $path): OpenSSLAsymmetricKey
    {
        $contents = file_get_contents($path);
        $privateKey = $contents === false ? false : @openssl_pkey_get_private($contents, '');
        if ($privateKey === false) {
            throw new DicomTlsConfigurationException('Der private Schlüssel des DICOM-TLS-Profils ist ungültig oder verschlüsselt. Es sind nur unverschlüsselte, dateisystemseitig geschützte Schlüssel zulässig.');
        }

        return $privateKey;
    }

    private function assertCertificateIsCurrent(OpenSSLCertificate $certificate): void
    {
        $details = openssl_x509_parse($certificate);
        $now = time();
        if ($details === false || ($details['validFrom_time_t'] ?? PHP_INT_MAX) > $now || ($details['validTo_time_t'] ?? 0) < $now) {
            throw new DicomTlsConfigurationException('Das Client-Zertifikat des DICOM-TLS-Profils ist noch nicht gültig oder abgelaufen.');
        }
    }

    private function isWithin(string $path, string $root): bool
    {
        $prefix = rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        return str_starts_with($path.DIRECTORY_SEPARATOR, $prefix);
    }
}
