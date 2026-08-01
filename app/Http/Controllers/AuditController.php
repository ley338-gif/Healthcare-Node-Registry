<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Organization;
use App\Models\SecurityEvent;
use App\Models\Site;
use App\Models\System;
use App\Models\User;
use App\Services\Audit\RegistryHistoryService;
use App\Services\Audit\RegistryHistoryViewService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AuditController extends Controller
{
    private const FILTERS = ['history_search', 'history_from', 'history_to', 'history_type', 'history_user', 'object_type', 'organization', 'site', 'department', 'system', 'only_errors', 'only_tests', 'sort'];

    public function index(Request $request, RegistryHistoryService $history, RegistryHistoryViewService $view): Response
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $filters = $request->only(self::FILTERS);
        $global = $history->global($user);
        $base = $this->baseQuery($history, $user, $filters);
        $data = $view->present($this->auditQuery(clone $base, $filters), $filters, 'page', 50);

        return Inertia::render('Audit/Index', [
            'events' => $data['history'], 'filters' => $filters, 'stats' => $this->stats($global),
            'eventTypes' => $data['historyEventTypes'],
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(['public_id', 'name']),
            'organizations' => Organization::query()->orderBy('name')->get(['public_id', 'name']),
            'sites' => Site::query()->orderBy('name')->get(['public_id', 'name']),
            'departments' => Department::query()->orderBy('name')->get(['public_id', 'name']),
            'systems' => System::query()->orderBy('name')->get(['public_id', 'name']),
        ]);
    }

    public function export(Request $request, string $format, RegistryHistoryService $history, RegistryHistoryViewService $view): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $format === 'csv', 403);
        $filters = $request->only(self::FILTERS);
        $events = $view->applyFilters($this->auditQuery($this->baseQuery($history, $user, $filters), $filters), $filters)->cursor();

        return response()->streamDownload(function () use ($events): void {
            $out = fopen('php://output', 'wb');
            if ($out === false) {
                return;
            }
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Zeitpunkt', 'Benutzer', 'Aktion', 'Objekttyp', 'Objekt-ID', 'Details', 'IP-Adresse'], ';');
            foreach ($events as $event) {
                $meta = $event->metadata;
                fputcsv($out, [$event->occurred_at->format('d.m.Y H:i:s'), $meta['actor_public_id'] ?? 'System', $event->event_type, class_basename((string) $event->subject_type), $event->subject_public_id, $meta['details'] ?? implode(', ', $meta['changed_fields'] ?? []), $meta['ip_address'] ?? ''], ';');
            }
            fclose($out);
        }, 'audit-'.now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @param  Builder<SecurityEvent>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<SecurityEvent>
     */
    private function auditQuery(Builder $query, array $filters): Builder
    {
        $type = trim((string) ($filters['object_type'] ?? ''));
        $sort = ($filters['sort'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->reorder('occurred_at', $sort)
            ->when($type !== '', fn (Builder $q) => $q->where('subject_type', 'like', '%\\'.$type))
            ->when(($filters['only_errors'] ?? '') === '1', fn (Builder $q) => $q->where(fn (Builder $x) => $x->where('metadata->status', 'failed')->orWhere('event_type', 'like', '%failed%')))
            ->when(($filters['only_tests'] ?? '') === '1', fn (Builder $q) => $q->where(fn (Builder $x) => $x->where('event_type', 'like', 'diagnostics.%')->orWhere('event_type', 'like', '%test%')));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<SecurityEvent>
     */
    private function baseQuery(RegistryHistoryService $history, User $user, array $filters): Builder
    {
        $global = $history->global($user);
        $contexts = [
            'system' => System::class,
            'department' => Department::class,
            'site' => Site::class,
            'organization' => Organization::class,
        ];
        foreach ($contexts as $filter => $model) {
            $publicId = trim((string) ($filters[$filter] ?? ''));
            if ($publicId !== '') {
                $context = $model::query()->where('public_id', $publicId)->firstOrFail();

                return $history->forContext($user, $context);
            }
        }

        return $global;
    }

    /**
     * @param  Builder<SecurityEvent>  $query
     * @return array<string, int>
     */
    private function stats(Builder $query): array
    {
        return ['total' => (clone $query)->count(), 'changes' => (clone $query)->where('event_type', 'like', '%.updated')->count(), 'created' => (clone $query)->where('event_type', 'like', '%.created')->count(), 'archived' => (clone $query)->where('event_type', 'like', '%.archived')->count(), 'documents' => (clone $query)->where('event_type', 'like', 'document.%')->count(), 'tests' => (clone $query)->where('event_type', 'like', 'diagnostics.%')->count()];
    }
}
