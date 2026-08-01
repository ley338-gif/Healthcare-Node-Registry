<?php

namespace App\Services\Documents;

use App\Models\Department;
use App\Models\Organization;
use App\Models\RegistryDocument;
use App\Models\RegistryDocumentVersion;
use App\Models\Site;
use App\Models\System;
use App\Models\User;
use App\Support\RegistryAudit;
use App\Support\RegistryDocumentCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class RegistryDocumentUploadService
{
    public function __construct(
        private readonly RegistryDocumentFileInspector $inspector,
        private readonly MalwareScanner $scanner,
        private readonly RegistryAudit $audit,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function upload(Organization|Site|Department|System $context, User $user, UploadedFile $file, array $attributes): RegistryDocument
    {
        $inspection = $this->inspector->inspect($file);
        $duplicate = RegistryDocumentVersion::query()
            ->where('sha256', $inspection['sha256'])
            ->whereHas('document', fn ($query) => $query
                ->where('documentable_type', $context::class)
                ->where('documentable_id', $context->getKey()))
            ->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['file' => 'Diese Datei ist für den Eintrag bereits vorhanden.']);
        }

        $scan = $this->scanner->scan($file->getRealPath());
        if (! in_array($scan->status, ['pending', 'clean', 'infected', 'failed', 'unavailable'], true)) {
            throw new \RuntimeException('Ungültiger Malware-Scanstatus.');
        }
        $disk = (string) config('registry_documents.disk');
        $storedFilename = Str::uuid7().'.'.$inspection['extension'];
        $storagePath = now()->format('Y/m').'/'.$storedFilename;
        Storage::disk($disk)->put($storagePath, file_get_contents($file->getRealPath()));

        try {
            return DB::transaction(function () use ($context, $user, $file, $attributes, $inspection, $scan, $disk, $storedFilename, $storagePath): RegistryDocument {
                $document = $context->documents()->create([
                    'title' => $attributes['title'], 'description' => $attributes['description'] ?? null,
                    'category' => RegistryDocumentCategory::from($attributes['category']),
                    'visibility' => $attributes['visibility'], 'status' => 'active',
                    'valid_from' => $attributes['valid_from'] ?? null, 'valid_until' => $attributes['valid_until'] ?? null,
                    'contract_reference' => $attributes['contract_reference'] ?? null,
                    'tags' => array_values(array_unique($attributes['tags'] ?? [])),
                    'created_by' => $user->id, 'updated_by' => $user->id,
                ]);
                $version = $document->versions()->create([
                    'version_number' => 1, 'original_filename' => $this->safeOriginalName($file),
                    'stored_filename' => $storedFilename, 'storage_disk' => $disk, 'storage_path' => $storagePath,
                    'mime_type' => $inspection['mime_type'], 'file_extension' => $inspection['extension'],
                    'size_bytes' => $inspection['size_bytes'], 'sha256' => $inspection['sha256'],
                    'uploaded_by' => $user->id, 'uploaded_at' => now(), 'change_note' => null,
                    'malware_scan_status' => $scan->status, 'malware_scan_message' => $scan->message,
                    'metadata' => [],
                ]);
                $document->update(['current_version_id' => $version->id]);
                $this->audit->record('document.created', $context, $user, [
                    'document_public_id' => $document->public_id, 'version_public_id' => $version->public_id,
                    'document_title' => $document->title, 'version_number' => 1,
                    'category' => $document->category->value, 'scan_status' => $scan->status, 'status' => 'success',
                ]);
                $this->recordScanEvent($scan, $context, $user, $document, $version);

                return $document;
            });
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($storagePath);
            throw $exception;
        }
    }

    public function uploadVersion(RegistryDocument $document, Organization|Site|Department|System $context, User $user, UploadedFile $file, string $changeNote): RegistryDocumentVersion
    {
        abort_unless($document->documentable_type === $context::class && $document->documentable_id === $context->getKey(), 404);
        $inspection = $this->inspector->inspect($file);
        if ($document->versions()->where('sha256', $inspection['sha256'])->exists()) {
            throw ValidationException::withMessages(['file' => 'Diese Datei ist bereits als Version vorhanden.']);
        }
        $scan = $this->scanner->scan($file->getRealPath());
        if (! in_array($scan->status, ['pending', 'clean', 'infected', 'failed', 'unavailable'], true)) {
            throw new \RuntimeException('Ungültiger Malware-Scanstatus.');
        }
        $disk = (string) config('registry_documents.disk');
        $storedFilename = Str::uuid7().'.'.$inspection['extension'];
        $storagePath = now()->format('Y/m').'/'.$storedFilename;
        Storage::disk($disk)->put($storagePath, file_get_contents($file->getRealPath()));

        try {
            return DB::transaction(function () use ($document, $context, $user, $file, $changeNote, $inspection, $scan, $disk, $storedFilename, $storagePath): RegistryDocumentVersion {
                $locked = RegistryDocument::query()->lockForUpdate()->findOrFail($document->id);
                $version = $locked->versions()->create([
                    'version_number' => ((int) $locked->versions()->max('version_number')) + 1,
                    'original_filename' => $this->safeOriginalName($file),
                    'stored_filename' => $storedFilename, 'storage_disk' => $disk, 'storage_path' => $storagePath,
                    'mime_type' => $inspection['mime_type'], 'file_extension' => $inspection['extension'],
                    'size_bytes' => $inspection['size_bytes'], 'sha256' => $inspection['sha256'],
                    'uploaded_by' => $user->id, 'uploaded_at' => now(), 'change_note' => $changeNote,
                    'malware_scan_status' => $scan->status, 'malware_scan_message' => $scan->message, 'metadata' => [],
                ]);
                $locked->update(['current_version_id' => $version->id, 'updated_by' => $user->id]);
                $this->audit->record('document.version_uploaded', $context, $user, [
                    'document_public_id' => $locked->public_id, 'version_public_id' => $version->public_id,
                    'document_title' => $locked->title, 'version_number' => $version->version_number,
                    'scan_status' => $scan->status, 'status' => 'success',
                ]);
                $this->recordScanEvent($scan, $context, $user, $locked, $version);

                return $version;
            });
        } catch (Throwable $exception) {
            Storage::disk($disk)->delete($storagePath);
            throw $exception;
        }
    }

    private function safeOriginalName(UploadedFile $file): string
    {
        $name = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';

        return mb_substr(trim($name), 0, 255) ?: 'document.'.$file->getClientOriginalExtension();
    }

    private function recordScanEvent(MalwareScanResult $scan, Organization|Site|Department|System $context, User $user, RegistryDocument $document, RegistryDocumentVersion $version): void
    {
        $eventType = match ($scan->status) {
            'failed' => 'document.scan_failed',
            'infected' => 'document.scan_infected',
            default => null,
        };
        if ($eventType === null) {
            return;
        }
        $this->audit->record($eventType, $context, $user, [
            'document_public_id' => $document->public_id,
            'document_title' => $document->title,
            'version_public_id' => $version->public_id,
            'version_number' => $version->version_number,
            'scan_status' => $scan->status,
            'status' => $scan->status === 'infected' ? 'failed' : 'warning',
        ]);
    }
}
