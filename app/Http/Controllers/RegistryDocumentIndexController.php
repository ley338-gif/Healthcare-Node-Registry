<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Organization;
use App\Models\Site;
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
            'documentTargets' => [
                'organizations' => Organization::query()->active()->orderBy('name')->get(['public_id', 'name']),
                'sites' => Site::query()->active()->orderBy('name')->get(['public_id', 'name']),
                'departments' => Department::query()->active()->orderBy('name')->get(['public_id', 'name']),
                'systems' => System::query()->active()->orderBy('name')->get(['public_id', 'name']),
            ],
            'canUploadDocuments' => $user->hasPermission('documents.upload'),
            'canManageDocumentVersions' => $user->hasPermission('documents.manage_versions'),
            'canDownloadDocuments' => $user->hasPermission('documents.download'),
            'canViewDocuments' => $user->hasPermission('documents.view'),
            'canUpdateDocuments' => $user->hasPermission('documents.update'),
            'canArchiveDocuments' => $user->hasPermission('documents.archive'),
        ]);
    }
}
