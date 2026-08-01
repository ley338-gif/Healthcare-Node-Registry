<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DicomNode;
use App\Models\Organization;
use App\Models\Site;
use App\Models\System;
use App\Models\User;
use App\Services\Audit\RegistryHistoryService;
use App\Services\Audit\RegistryHistoryViewService;
use App\Services\Documents\RegistryDocumentQueryService;
use App\Support\RegistryDocumentCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class OrganizationStructureController extends Controller
{
    public function __invoke(
        Request $request,
        RegistryHistoryService $historyService,
        RegistryHistoryViewService $historyViewService,
        RegistryDocumentQueryService $documentQueryService,
    ): Response {
        $organizations = Organization::query()
            ->active()
            ->with([
                'sites' => fn ($query) => $query
                    ->active()
                    ->with([
                        'departments' => fn ($departmentQuery) => $departmentQuery
                            ->active()
                            ->orderBy('name'),
                    ])
                    ->orderBy('name'),
            ])
            ->orderBy('name')
            ->get([
                'id',
                'public_id',
                'name',
                'short_name',
                'description',
                'updated_at',
            ]);

        $systems = System::query()
            ->active()
            ->with([
                'dicomNodes' => fn ($query) => $query
                    ->active()
                    ->withCount([
                        'outgoingConnections',
                        'incomingConnections',
                    ]),
            ])
            ->orderBy('name')
            ->get([
                'id',
                'public_id',
                'organization_id',
                'site_id',
                'department_id',
                'name',
                'system_type',
                'status',
                'hostname',
                'ip_address',
                'vendor',
                'product',
            ])
            ->map(function (System $system): array {
                /** @var Collection<int, DicomNode> $nodes */
                $nodes = $system->dicomNodes;

                return [
                    'public_id' => $system->public_id,
                    'organization_id' => $system->organization_id,
                    'site_id' => $system->site_id,
                    'department_id' => $system->department_id,
                    'name' => $system->name,
                    'system_type' => $system->system_type,
                    'status' => $system->status,
                    'hostname' => $system->hostname,
                    'ip_address' => $system->ip_address,
                    'vendor' => $system->vendor,
                    'product' => $system->product,
                    'dicom_nodes_count' => $nodes->count(),
                    'connection_count' => $nodes->sum(
                        fn ($node): int => (int) $node->outgoing_connections_count
                            + (int) $node->incoming_connections_count,
                    ),
                    'verified_nodes_count' => $nodes
                        ->filter(fn ($node): bool => $node->last_verified_at !== null)
                        ->count(),
                    'latest_verified_at' => $nodes
                        ->max('last_verified_at')
                        ?->toIso8601String(),
                ];
            })
            ->values();

        $selectedType = trim((string) $request->query('selected_type', 'organization'));
        $selectedPublicId = trim((string) $request->query('selected_id', ''));
        $selectedContext = $this->resolveContext($selectedType, $selectedPublicId, $organizations->first());
        $historyView = [
            'history' => null,
            'historyStats' => ['total' => 0, 'today' => 0, 'last7Days' => 0, 'last30Days' => 0],
            'historyFilters' => $request->only([
                'history_from', 'history_to', 'history_type', 'history_user', 'history_search', 'history_status',
                'history_scope',
            ]),
            'historyEventTypes' => collect(),
            'historyUsers' => collect(),
        ];
        $documentation = collect();
        $documents = ['data' => [], 'links' => [], 'total' => 0];
        $documentUploaders = collect();
        $documentFilters = $request->only(RegistryDocumentQueryService::FILTER_KEYS);
        $canManageDocumentation = false;
        $includeDescendants = $request->query('history_scope', 'descendants') !== 'direct';
        $user = $request->user();

        $mayLoadDefaultHistory = $selectedContext instanceof Model
            && $user instanceof User
            && Gate::forUser($user)->allows('view', $selectedContext);

        if ($selectedContext instanceof Model && $user instanceof User && $mayLoadDefaultHistory) {
            $documentation = $selectedContext->documentation()
                ->with('updatedByUser:id,public_id,name')
                ->orderBy('section')
                ->get();
            $canManageDocumentation = Gate::forUser($user)->allows('update', $selectedContext);
            $documents = $documentQueryService->paginate($selectedContext, $documentFilters);
            $documentUploaders = $documentQueryService->uploaders($selectedContext);
        }

        if (
            $selectedContext instanceof Model
            && $user instanceof User
            && ($selectedPublicId !== '' || $mayLoadDefaultHistory)
        ) {
            $historyView = $historyViewService->present(
                $historyService->forContext($user, $selectedContext, $includeDescendants),
                $historyView['historyFilters'],
            );
        }

        return Inertia::render('OrganizationStructure/Index', [
            'summary' => [
                'organizations' => Organization::query()->active()->count(),
                'sites' => Site::query()->active()->count(),
                'departments' => Department::query()->active()->count(),
            ],
            'organizations' => $organizations,
            'systems' => $systems,
            'selectedContext' => $selectedContext instanceof Model ? [
                'type' => match (true) {
                    $selectedContext instanceof Site => 'site',
                    $selectedContext instanceof Department => 'department',
                    default => 'organization',
                },
                'public_id' => (string) $selectedContext->getAttribute('public_id'),
            ] : null,
            ...$historyView,
            'documentation' => $documentation,
            'documents' => $documents,
            'documentFilters' => $documentFilters,
            'documentUploaders' => $documentUploaders,
            'documentCategories' => RegistryDocumentCategory::options(),
            'canUploadDocuments' => $user?->hasPermission('documents.upload') ?? false,
            'canManageDocumentVersions' => $user?->hasPermission('documents.manage_versions') ?? false,
            'canDownloadDocuments' => $user?->hasPermission('documents.download') ?? false,
            'canViewDocuments' => $user?->hasPermission('documents.view') ?? false,
            'canUpdateDocuments' => $user?->hasPermission('documents.update') ?? false,
            'canArchiveDocuments' => $user?->hasPermission('documents.archive') ?? false,
            'canManageDocumentation' => $canManageDocumentation,
        ]);
    }

    private function resolveContext(
        string $type,
        string $publicId,
        ?Organization $fallback,
    ): Organization|Site|Department|null {
        if ($publicId === '') {
            return $fallback;
        }

        return match ($type) {
            'site' => Site::query()->active()->where('public_id', $publicId)->firstOrFail(),
            'department' => Department::query()->active()->where('public_id', $publicId)->firstOrFail(),
            default => Organization::query()->active()->where('public_id', $publicId)->firstOrFail(),
        };
    }
}
