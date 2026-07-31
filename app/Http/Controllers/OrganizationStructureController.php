<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DicomNode;
use App\Models\Organization;
use App\Models\Site;
use App\Models\System;
use App\Models\User;
use App\Services\Audit\RegistryHistoryService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class OrganizationStructureController extends Controller
{
    public function __invoke(Request $request, RegistryHistoryService $historyService): Response
    {
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
        $history = null;
        $historyStats = ['total' => 0, 'today' => 0, 'last7Days' => 0, 'last30Days' => 0];
        $historyEventTypes = collect();
        $historyUsers = collect();
        $includeDescendants = $request->query('history_scope', 'descendants') !== 'direct';
        $user = $request->user();

        $mayLoadDefaultHistory = $selectedContext instanceof Model
            && $user instanceof User
            && Gate::forUser($user)->allows('view', $selectedContext);

        if (
            $selectedContext instanceof Model
            && $user instanceof User
            && ($selectedPublicId !== '' || $mayLoadDefaultHistory)
        ) {
            $historyBase = $historyService->forContext($user, $selectedContext, $includeDescendants);
            $historyQuery = clone $historyBase;
            $from = trim((string) $request->query('history_from', ''));
            $to = trim((string) $request->query('history_to', ''));
            $type = trim((string) $request->query('history_type', ''));
            $actor = trim((string) $request->query('history_user', ''));
            $search = trim((string) $request->query('history_search', ''));
            $status = trim((string) $request->query('history_status', ''));

            $historyQuery
                ->when($from !== '', fn ($query) => $query->whereDate('occurred_at', '>=', $from))
                ->when($to !== '', fn ($query) => $query->whereDate('occurred_at', '<=', $to))
                ->when($type !== '', fn ($query) => $query->where('event_type', $type))
                ->when($actor !== '', fn ($query) => $query->where('metadata->actor_public_id', $actor))
                ->when($status !== '', fn ($query) => $query->where('metadata->status', $status))
                ->when($search !== '', fn ($query) => $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('event_type', 'ilike', "%{$search}%")
                        ->orWhere('subject_public_id', 'ilike', "%{$search}%");
                }));

            $actorIds = (clone $historyBase)->get()->pluck('metadata')->map(
                fn (mixed $metadata): ?string => is_array($metadata) && is_string($metadata['actor_public_id'] ?? null)
                    ? $metadata['actor_public_id']
                    : null,
            )->filter()->unique()->values();
            $actors = User::query()->whereIn('public_id', $actorIds)->pluck('name', 'public_id');
            $now = CarbonImmutable::now();
            $history = $historyQuery->paginate(15, ['*'], 'history_page')->withQueryString()->through(
                fn ($event): array => [
                    'event_id' => $event->event_id,
                    'event_type' => $event->event_type,
                    'subject_type' => class_basename((string) $event->subject_type),
                    'subject_public_id' => $event->subject_public_id,
                    'actor_name' => $actors->get($event->metadata['actor_public_id'] ?? '') ?? 'System',
                    'metadata' => $event->metadata,
                    'occurred_at' => $event->occurred_at->toIso8601String(),
                ],
            );
            $historyStats = [
                'total' => (clone $historyBase)->count(),
                'today' => (clone $historyBase)->where('occurred_at', '>=', $now->startOfDay())->count(),
                'last7Days' => (clone $historyBase)->where('occurred_at', '>=', $now->subDays(7))->count(),
                'last30Days' => (clone $historyBase)->where('occurred_at', '>=', $now->subDays(30))->count(),
            ];
            $historyEventTypes = (clone $historyBase)->reorder('event_type')->distinct()->pluck('event_type');
            $historyUsers = $actors->map(fn (string $name, string $publicId): array => [
                'public_id' => $publicId,
                'name' => $name,
            ])->values();
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
            'history' => $history,
            'historyStats' => $historyStats,
            'historyFilters' => $request->only([
                'history_from', 'history_to', 'history_type', 'history_user', 'history_search', 'history_status',
                'history_scope',
            ]),
            'historyEventTypes' => $historyEventTypes,
            'historyUsers' => $historyUsers,
        ]);
    }

    private function resolveContext(string $type, string $publicId, ?Organization $fallback): ?Model
    {
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
