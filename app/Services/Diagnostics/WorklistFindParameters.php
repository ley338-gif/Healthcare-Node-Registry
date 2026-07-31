<?php

namespace App\Services\Diagnostics;

final readonly class WorklistFindParameters
{
    public function __construct(
        public string $callingAeTitle,
        public string $calledAeTitle,
        public ?string $scheduledStationAeTitle,
        public string $examinationDate,
        public ?string $examinationDateTo,
        public ?string $modality,
        public ?string $patientName,
        public ?string $patientId,
        public ?string $accessionNumber,
    ) {}

    public function dicomDateRange(): string
    {
        $from = str_replace('-', '', $this->examinationDate);
        $to = $this->examinationDateTo === null
            ? $from
            : str_replace('-', '', $this->examinationDateTo);

        return $from === $to ? $from : "{$from}-{$to}";
    }
}
