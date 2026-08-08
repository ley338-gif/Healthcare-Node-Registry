<?php

namespace App\Services\Documents;

use App\Models\RegistryDocumentVersion;
use App\Support\RegistryAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

final class RegistryDocumentRescanService
{
    private const RETRYABLE_STATUSES = ['pending', 'failed', 'unavailable'];

    public function __construct(
        private readonly MalwareScanner $scanner,
        private readonly RegistryAudit $audit,
    ) {}

    /** @return array{scanned:int,clean:int,infected:int,failed:int,unavailable:int} */
    public function rescan(int $limit): array
    {
        $counts = ['scanned' => 0, 'clean' => 0, 'infected' => 0, 'failed' => 0, 'unavailable' => 0];
        $versions = RegistryDocumentVersion::query()
            ->with('document.documentable')
            ->whereIn('malware_scan_status', self::RETRYABLE_STATUSES)
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get();

        foreach ($versions as $version) {
            $disk = Storage::disk($version->storage_disk);
            $result = $disk->exists($version->storage_path)
                ? $this->scanner->scan($disk->path($version->storage_path))
                : new MalwareScanResult('failed', 'Die gespeicherte Dokumentdatei wurde nicht gefunden.');

            if (! in_array($result->status, ['clean', 'infected', 'failed', 'unavailable'], true)) {
                $result = new MalwareScanResult('failed', 'Der Malware-Scanner lieferte einen ungültigen Status.');
            }

            $version->update([
                'malware_scan_status' => $result->status,
                'malware_scan_message' => $result->message,
            ]);
            $counts['scanned']++;
            if ($result->status === 'clean') {
                $counts['clean']++;
            } elseif ($result->status === 'infected') {
                $counts['infected']++;
            } elseif ($result->status === 'failed') {
                $counts['failed']++;
            } else {
                $counts['unavailable']++;
            }
            $this->recordResult($version, $result);
        }

        return $counts;
    }

    private function recordResult(RegistryDocumentVersion $version, MalwareScanResult $result): void
    {
        $subject = $version->document->documentable;
        if (! $subject instanceof Model) {
            return;
        }

        $this->audit->record('document.scan_rescanned', $subject, null, [
            'document_public_id' => $version->document->public_id,
            'document_title' => $version->document->title,
            'version_public_id' => $version->public_id,
            'version_number' => $version->version_number,
            'scan_status' => $result->status,
            'status' => match ($result->status) {
                'clean' => 'success',
                'infected' => 'failed',
                default => 'warning',
            },
        ]);
    }
}
