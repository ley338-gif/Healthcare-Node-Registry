<?php

namespace App\Services\Diagnostics;

final readonly class PacsQueryParameters
{
    public function __construct(
        public string $callingAeTitle, public string $calledAeTitle,
        public ?string $patientName, public ?string $patientId,
        public ?string $accessionNumber, public ?string $studyInstanceUid,
        public ?string $modality, public ?string $studyDate,
        public ?string $studyDateTo, public ?string $studyDescription,
    ) {}

    public function dicomDateRange(): ?string
    {
        if ($this->studyDate === null) {
            return null;
        }
        $from = str_replace('-', '', $this->studyDate);
        $to = $this->studyDateTo === null ? $from : str_replace('-', '', $this->studyDateTo);

        return $from === $to ? $from : "{$from}-{$to}";
    }
}
