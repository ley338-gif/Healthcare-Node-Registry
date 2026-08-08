<?php

namespace App\Services\Dicom;

final readonly class DicomTlsProfile
{
    public function __construct(
        public string $reference,
        public string $privateKeyPath,
        public string $certificatePath,
        public string $caCertificatePath,
    ) {}

    /** @return list<string> */
    public function dcmtkArguments(): array
    {
        return [
            '+tls',
            $this->privateKeyPath,
            $this->certificatePath,
            '-pw',
            '+cf',
            $this->caCertificatePath,
            '-rc',
            '+py',
        ];
    }

    /** @return array{enabled: true, profile: string, private_key: string, certificate: string, ca_certificate: string} */
    public function pythonPayload(): array
    {
        return [
            'enabled' => true,
            'profile' => $this->reference,
            'private_key' => $this->privateKeyPath,
            'certificate' => $this->certificatePath,
            'ca_certificate' => $this->caCertificatePath,
        ];
    }

    /** @return resource */
    public function clientStreamContext()
    {
        return stream_context_create([
            'ssl' => [
                'local_cert' => $this->certificatePath,
                'local_pk' => $this->privateKeyPath,
                'cafile' => $this->caCertificatePath,
                'verify_peer' => true,
                'verify_peer_name' => false,
                'allow_self_signed' => false,
                'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT,
            ],
        ]);
    }
}
