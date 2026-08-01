<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegistryDocumentRequest;
use App\Http\Requests\StoreRegistryDocumentVersionRequest;
use App\Models\Department;
use App\Models\Organization;
use App\Models\RegistryDocument;
use App\Models\RegistryDocumentVersion;
use App\Models\Site;
use App\Models\System;
use App\Models\User;
use App\Services\Documents\RegistryDocumentUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class RegistryDocumentController extends Controller
{
    public function store(StoreRegistryDocumentRequest $request, string $documentableType, string $documentable, RegistryDocumentUploadService $uploads): RedirectResponse
    {
        $context = $this->resolve($documentableType, $documentable);
        Gate::authorize('view', $context);
        abort_if($context->getAttribute('archived_at') !== null, 422, 'Archivierte Einträge akzeptieren keine Dokumente.');
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $uploads->upload($context, $user, $request->file('file'), $request->validated());

        return back()->with('success', 'Dokument wurde sicher gespeichert.');
    }

    public function storeVersion(StoreRegistryDocumentVersionRequest $request, RegistryDocument $registryDocument, RegistryDocumentUploadService $uploads): RedirectResponse
    {
        abort_if($registryDocument->archived_at !== null, 422, 'Archivierte Dokumente akzeptieren keine Versionen.');
        $context = $registryDocument->documentable;
        abort_unless($context instanceof Organization || $context instanceof Site || $context instanceof Department || $context instanceof System, 404);
        Gate::authorize('view', $context);
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $uploads->uploadVersion($registryDocument, $context, $user, $request->file('file'), (string) $request->validated('change_note'));

        return back()->with('success', 'Neue Dokumentversion wurde gespeichert.');
    }

    public function downloadVersion(RegistryDocumentVersion $registryDocumentVersion): StreamedResponse
    {
        Gate::authorize('documents.download');
        $document = $registryDocumentVersion->document;
        abort_if($document->archived_at !== null, 404);
        $context = $document->documentable;
        abort_unless($context instanceof Organization || $context instanceof Site || $context instanceof Department || $context instanceof System, 404);
        Gate::authorize('view', $context);
        abort_unless($registryDocumentVersion->malware_scan_status === 'clean', 423, 'Die Dokumentversion ist nicht zum Download freigegeben.');
        abort_unless(Storage::disk($registryDocumentVersion->storage_disk)->exists($registryDocumentVersion->storage_path), 404);

        return Storage::disk($registryDocumentVersion->storage_disk)->download(
            $registryDocumentVersion->storage_path,
            $registryDocumentVersion->original_filename,
            ['Content-Type' => $registryDocumentVersion->mime_type, 'X-Content-Type-Options' => 'nosniff'],
        );
    }

    public function previewVersion(RegistryDocumentVersion $registryDocumentVersion): BinaryFileResponse
    {
        Gate::authorize('documents.view');
        $document = $registryDocumentVersion->document;
        abort_if($document->archived_at !== null, 404);
        $context = $document->documentable;
        abort_unless($context instanceof Organization || $context instanceof Site || $context instanceof Department || $context instanceof System, 404);
        Gate::authorize('view', $context);
        abort_unless($registryDocumentVersion->malware_scan_status === 'clean', 423, 'Die Dokumentversion ist nicht zur Vorschau freigegeben.');
        abort_unless($registryDocumentVersion->mime_type === 'application/pdf' && $registryDocumentVersion->file_extension === 'pdf', 415);
        $disk = Storage::disk($registryDocumentVersion->storage_disk);
        abort_unless($disk->exists($registryDocumentVersion->storage_path), 404);

        return response()
            ->file($disk->path($registryDocumentVersion->storage_path), [
                'Content-Type' => 'application/pdf',
                'Cache-Control' => 'private, no-store, max-age=0',
                'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'self'",
                'X-Content-Type-Options' => 'nosniff',
                'X-Frame-Options' => 'SAMEORIGIN',
            ])
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $registryDocumentVersion->original_filename);
    }

    private function resolve(string $type, string $publicId): Organization|Site|Department|System
    {
        /** @var class-string<Organization|Site|Department|System> $model */
        $model = match ($type) {
            'organizations' => Organization::class, 'sites' => Site::class,
            'departments' => Department::class, 'systems' => System::class,
            default => abort(404),
        };

        return $model::query()->where('public_id', $publicId)->firstOrFail();
    }
}
