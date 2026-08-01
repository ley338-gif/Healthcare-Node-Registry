<?php

namespace App\Services\Audit;

use App\Models\SecurityEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class RegistryHistoryViewService
{
    public function __construct(private readonly RegistryAuditEntityResolver $entityResolver) {}

    /**
     * @param  Builder<SecurityEvent>  $baseQuery
     * @param  array<string, mixed>  $filters
     * @return array{
     *   history: LengthAwarePaginator<int, array<string, mixed>>,
     *   historyStats: array{total: int, today: int, last7Days: int, last30Days: int},
     *   historyFilters: array<string, mixed>,
     *   historyEventTypes: Collection<int, string>,
     *   historyUsers: Collection<int, array{public_id: string, name: string}>
     * }
     */
    public function present(Builder $baseQuery, array $filters, string $pageName = 'history_page', int $perPage = 15): array
    {
        $filteredQuery = $this->applyFilters(clone $baseQuery, $filters);
        $history = $filteredQuery->paginate($perPage, ['*'], $pageName)->withQueryString();
        $actorIds = collect($history->items())->pluck('metadata')->map(
            fn (mixed $metadata): ?string => is_array($metadata) && is_string($metadata['actor_public_id'] ?? null)
                ? $metadata['actor_public_id']
                : null,
        )->filter()->unique()->values();
        $actors = User::query()->whereIn('public_id', $actorIds)->pluck('name', 'public_id');
        $now = CarbonImmutable::now();

        return [
            'history' => $history->through(
                fn (SecurityEvent $event): array => $this->event($event, $actors),
            ),
            'historyStats' => [
                'total' => (clone $baseQuery)->count(),
                'today' => (clone $baseQuery)->where('occurred_at', '>=', $now->startOfDay())->count(),
                'last7Days' => (clone $baseQuery)->where('occurred_at', '>=', $now->subDays(7))->count(),
                'last30Days' => (clone $baseQuery)->where('occurred_at', '>=', $now->subDays(30))->count(),
            ],
            'historyFilters' => $filters,
            'historyEventTypes' => (clone $baseQuery)->reorder('event_type')->distinct()->pluck('event_type'),
            'historyUsers' => $actors->map(fn (string $name, string $publicId): array => [
                'public_id' => $publicId,
                'name' => $name,
            ])->values(),
        ];
    }

    /**
     * Shared filter pipeline for contextual histories and the future global audit explorer.
     *
     * @param  Builder<SecurityEvent>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<SecurityEvent>
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        $from = trim((string) ($filters['history_from'] ?? ''));
        $to = trim((string) ($filters['history_to'] ?? ''));
        $type = trim((string) ($filters['history_type'] ?? ''));
        $actor = trim((string) ($filters['history_user'] ?? ''));
        $search = trim((string) ($filters['history_search'] ?? ''));
        $status = trim((string) ($filters['history_status'] ?? ''));

        return $query
            ->when($from !== '', fn (Builder $builder) => $builder->whereDate('occurred_at', '>=', $from))
            ->when($to !== '', fn (Builder $builder) => $builder->whereDate('occurred_at', '<=', $to))
            ->when($type !== '', fn (Builder $builder) => $builder->where('event_type', $type))
            ->when($actor !== '', fn (Builder $builder) => $builder->where('metadata->actor_public_id', $actor))
            ->when($status !== '', fn (Builder $builder) => $builder->where('metadata->status', $status))
            ->when($search !== '', fn (Builder $builder) => $builder->where(
                fn (Builder $searchQuery) => $searchQuery
                    ->where('event_type', 'ilike', "%{$search}%")
                    ->orWhere('subject_public_id', 'ilike', "%{$search}%"),
            ));
    }

    /**
     * @param  Collection<string, string>  $actors
     * @return array<string, mixed>
     */
    private function event(SecurityEvent $event, Collection $actors): array
    {
        $entity = $this->entityResolver->resolve($event);

        return [
            'event_id' => $event->event_id,
            'event_type' => $event->event_type,
            'subject_type' => $entity['type'],
            'subject_public_id' => $event->subject_public_id,
            'entity' => $entity,
            'actor_name' => $actors->get($event->metadata['actor_public_id'] ?? '') ?? 'System',
            'metadata' => $event->metadata,
            'occurred_at' => $event->occurred_at->toIso8601String(),
        ];
    }
}
