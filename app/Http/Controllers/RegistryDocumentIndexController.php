<?php

namespace App\Http\Controllers;

use App\Models\System;
use App\Models\User;
use App\Services\Documents\RegistryDocumentQueryService;
use App\Support\RegistryDocumentCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class RegistryDocumentIndexController extends Controller
{
    public function __invoke(Request $request, RegistryDocumentQueryService $documents): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->hasPermission('documents.view'), 403);
        Gate::authorize('viewAny', System::class);
        $filters = $request->only(RegistryDocumentQueryService::FILTER_KEYS);

        return Inertia::render('Documents/Index', [
            'documents' => $documents->paginateAll($filters),
            'documentFilters' => $filters,
            'documentUploaders' => $documents->allUploaders(),
            'documentCategories' => RegistryDocumentCategory::options(),
            'canManageDocumentVersions' => $user->hasPermission('documents.manage_versions'),
            'canDownloadDocuments' => $user->hasPermission('documents.download'),
            'canViewDocuments' => $user->hasPermission('documents.view'),
            'canUpdateDocuments' => $user->hasPermission('documents.update'),
            'canArchiveDocuments' => $user->hasPermission('documents.archive'),
        ]);
    }
}
