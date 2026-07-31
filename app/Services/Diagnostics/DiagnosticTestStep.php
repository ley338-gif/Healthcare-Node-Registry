<?php

namespace App\Services\Diagnostics;

final readonly class DiagnosticTestStep
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public string $name,
        public string $label,
        public DiagnosticTestStatus $status,
        public int $durationMilliseconds,
        public string $message,
        public array $details = [],
    ) {}

    /**
     * @return array{
     *     name: string,
     *     label: string,
     *     status: string,
     *     durationMilliseconds: int,
     *     message: string,
     *     details: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'label' => $this->label,
            'status' => $this->status->value,
            'durationMilliseconds' => $this->durationMilliseconds,
            'message' => $this->message,
            'details' => $this->details,
        ];
    }
}
