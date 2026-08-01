<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRegistryDocumentRequest;
use App\Models\Department;
use App\Models\Organization;
use App\Models\Site;
use App\Models\System;
use App\Models\User;
use App\Services\Documents\RegistryDocumentUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

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
