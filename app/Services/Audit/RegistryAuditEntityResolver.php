<?php

namespace App\Services\Audit;

use App\Models\Department;
use App\Models\DicomConnection;
use App\Models\DicomNode;
use App\Models\Organization;
use App\Models\SecurityEvent;
use App\Models\Site;
use App\Models\System;

final class RegistryAuditEntityResolver
{
    /** @return array{type: string, label: string, public_id: string|null, url: string|null} */
    public function resolve(SecurityEvent $event): array
    {
        $publicId = $event->subject_public_id;

        return match ($event->subject_type) {
            Organization::class => $this->context('Organization', 'Organisation', $publicId, '/organizations'),
            Site::class => $this->context('Site', 'Standort', $publicId, '/sites'),
            Department::class => $this->context('Department', 'Abteilung', $publicId, '/departments'),
            System::class => $this->context('System', 'System', $publicId, '/systems'),
            DicomNode::class => $this->context('DicomNode', 'DICOM-Knoten', $publicId, null),
            DicomConnection::class => $this->context('DicomConnection', 'DICOM-Verbindung', $publicId, null),
            default => $this->context(class_basename((string) $event->subject_type), 'Technisches Objekt', $publicId, null),
        };
    }

    /** @return array{type: string, label: string, public_id: string|null, url: string|null} */
    private function context(string $type, string $label, ?string $publicId, ?string $baseUrl): array
    {
        return [
            'type' => $type,
            'label' => $label,
            'public_id' => $publicId,
            'url' => $baseUrl !== null && $publicId !== null ? "{$baseUrl}?selected={$publicId}" : null,
        ];
    }
}
