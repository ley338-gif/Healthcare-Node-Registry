<?php

namespace App\Services\Audit;

use App\Models\Department;
use App\Models\DiagnosticTestRun;
use App\Models\DicomConnection;
use App\Models\DicomNode;
use App\Models\Organization;
use App\Models\RegistryDocument;
use App\Models\SecurityEvent;
use App\Models\Site;
use App\Models\System;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class RegistryAuditEntityResolver
{
    /** @var array<string, array{type: string, label: string, public_id: string|null, url: string|null, navigable: bool}> */
    private array $cache = [];

    /** @return array{type: string, label: string, public_id: string|null, url: string|null, navigable: bool} */
    public function resolve(SecurityEvent $event): array
    {
        $documentId = $this->metadataId($event, 'document_public_id');
        if (str_starts_with($event->event_type, 'document.') && $documentId !== null) {
            return $this->document($documentId);
        }

        $testId = $this->metadataId($event, 'test_id');
        if (str_starts_with($event->event_type, 'diagnostics.') && $testId !== null) {
            return $this->test($testId);
        }

        $publicId = $event->subject_public_id;
        $key = (string) $event->subject_type.':'.(string) $publicId;

        return $this->cache[$key] ??= match ($event->subject_type) {
            Organization::class => $this->registryEntity(Organization::class, 'Organization', 'Organisation', 'organization', $publicId),
            Site::class => $this->registryEntity(Site::class, 'Site', 'Standort', 'site', $publicId),
            Department::class => $this->registryEntity(Department::class, 'Department', 'Abteilung', 'department', $publicId),
            System::class => $this->system($publicId),
            DicomNode::class => $this->node($publicId),
            DicomConnection::class => $this->connection($publicId),
            RegistryDocument::class => $publicId !== null ? $this->document($publicId) : $this->missing('RegistryDocument', 'Dokument', null),
            DiagnosticTestRun::class => $publicId !== null ? $this->test($publicId) : $this->missing('DiagnosticTestRun', 'Test', null),
            User::class => $this->user($publicId),
            default => $this->missing(class_basename((string) $event->subject_type), 'Technisches Objekt', $publicId),
        };
    }

    /**
     * @param  class-string<Model>  $model
     * @return array{type: string, label: string, public_id: string|null, url: string|null, navigable: bool}
     */
    private function registryEntity(string $model, string $type, string $label, string $workspaceType, ?string $publicId): array
    {
        $entity = $publicId !== null ? $model::query()->where('public_id', $publicId)->first() : null;
        $active = $entity instanceof Model && $entity->getAttribute('archived_at') === null;

        return $this->context($type, $label, $publicId, $active ? "/structure?selected_type={$workspaceType}&selected_id={$publicId}" : null);
    }

    /** @return array{type: string, label: string, public_id: string|null, url: string|null, navigable: bool} */
    private function system(?string $publicId): array
    {
        $system = $publicId !== null ? System::query()->where('public_id', $publicId)->first() : null;
        $active = $system instanceof System && $system->archived_at === null;

        return $this->context('System', 'System', $publicId, $active ? "/systems/{$publicId}" : null);
    }

    /** @return array{type: string, label: string, public_id: string|null, url: string|null, navigable: bool} */
    private function node(?string $publicId): array
    {
        $node = $publicId !== null ? DicomNode::query()->with('system:id,public_id,archived_at')->where('public_id', $publicId)->first() : null;
        $active = $node instanceof DicomNode && $node->archived_at === null && $node->system->archived_at === null;

        return $this->context('DicomNode', 'DICOM-Knoten', $publicId, $active ? "/network?node={$publicId}" : null);
    }

    /** @return array{type: string, label: string, public_id: string|null, url: string|null, navigable: bool} */
    private function connection(?string $publicId): array
    {
        $connection = $publicId !== null ? DicomConnection::query()->where('public_id', $publicId)->first() : null;
        $active = $connection instanceof DicomConnection && $connection->archived_at === null;

        return $this->context('DicomConnection', 'DICOM-Verbindung', $publicId, $active ? "/network?connection={$publicId}" : null);
    }

    /** @return array{type: string, label: string, public_id: string|null, url: string|null, navigable: bool} */
    private function document(string $publicId): array
    {
        $key = 'document:'.$publicId;
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        $document = RegistryDocument::query()->where('public_id', $publicId)->first();
        $active = $document instanceof RegistryDocument && $document->archived_at === null && $document->status !== 'archived';

        return $this->cache[$key] = $this->context('RegistryDocument', 'Dokument', $publicId, $active ? "/documents?document={$publicId}" : null);
    }

    /** @return array{type: string, label: string, public_id: string|null, url: string|null, navigable: bool} */
    private function test(string $publicId): array
    {
        $key = 'test:'.$publicId;
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        $test = DiagnosticTestRun::query()->where('public_id', $publicId)->first();

        return $this->cache[$key] = $this->context('DiagnosticTestRun', 'Test', $publicId, $test instanceof DiagnosticTestRun ? "/tests?run={$publicId}" : null);
    }

    /** @return array{type: string, label: string, public_id: string|null, url: string|null, navigable: bool} */
    private function user(?string $publicId): array
    {
        $user = $publicId !== null ? User::query()->where('public_id', $publicId)->first() : null;

        return $this->context('User', 'Benutzer', $publicId, $user instanceof User && $user->is_active ? "/audit?history_user={$publicId}" : null);
    }

    private function metadataId(SecurityEvent $event, string $key): ?string
    {
        $value = $event->metadata[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /** @return array{type: string, label: string, public_id: string|null, url: string|null, navigable: bool} */
    private function missing(string $type, string $label, ?string $publicId): array
    {
        return $this->context($type, $label, $publicId, null);
    }

    /** @return array{type: string, label: string, public_id: string|null, url: string|null, navigable: bool} */
    private function context(string $type, string $label, ?string $publicId, ?string $url): array
    {
        return ['type' => $type, 'label' => $label, 'public_id' => $publicId, 'url' => $url, 'navigable' => $url !== null];
    }
}
