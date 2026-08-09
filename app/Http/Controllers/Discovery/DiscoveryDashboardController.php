<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\Controller;
use App\Models\DicomDiscoveryResult;
use App\Models\DiscoveredHost;
use App\Models\DiscoveryRun;
use App\Models\DiscoverySchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class DiscoveryDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        Gate::authorize('viewAny', DiscoveryRun::class);

        $lastRun = DiscoveryRun::query()->latest('id')->first();

        return Inertia::render('Discovery/Dashboard', [
            'stats' => [
                'runs_count' => DiscoveryRun::query()->count(),
                'hosts_found_count' => DiscoveredHost::query()->count(),
                'dicom_candidates_count' => DiscoveredHost::query()->whereHas('ports', fn ($q) => $q->where('is_dicom_candidate', true)->where('is_open', true))->count(),
                'confirmed_systems_count' => DiscoveredHost::query()->where('status', DiscoveredHost::STATUS_CONFIRMED)->count(),
                'unreviewed_count' => DiscoveredHost::query()->whereIn('status', [DiscoveredHost::STATUS_DISCOVERED, DiscoveredHost::STATUS_REVIEWING])->count(),
                'failed_checks_count' => DicomDiscoveryResult::query()->where('echo_successful', false)->count(),
            ],
            'lastRun' => $lastRun ? [
                'public_id' => $lastRun->public_id,
                'name' => $lastRun->name,
                'status' => $lastRun->status,
                'progress_percentage' => $lastRun->progress_percentage,
                'started_at' => $lastRun->started_at?->toIso8601String(),
                'finished_at' => $lastRun->finished_at?->toIso8601String(),
                'ip_range' => $lastRun->ip_range,
                'found_hosts_count' => $lastRun->found_hosts_count,
                'dicom_candidates_count' => $lastRun->dicom_candidates_count,
                'confirmed_systems_count' => $lastRun->hosts()->where('status', DiscoveredHost::STATUS_CONFIRMED)->count(),
                'open_reviews_count' => $lastRun->hosts()->whereIn('status', [DiscoveredHost::STATUS_DISCOVERED, DiscoveredHost::STATUS_REVIEWING])->count(),
            ] : null,
            'canRun' => $request->user()?->can('create', DiscoveryRun::class) ?? false,
            'schedules' => DiscoverySchedule::query()
                ->with('lastRun:id,public_id,name,status')
                ->orderByDesc('active')
                ->orderBy('next_run_at')
                ->get()
                ->map(fn (DiscoverySchedule $schedule): array => [
                    'public_id' => $schedule->public_id,
                    'name' => $schedule->name,
                    'frequency' => $schedule->frequency,
                    'scheduled_time' => substr($schedule->scheduled_time, 0, 5),
                    'day_of_week' => $schedule->day_of_week,
                    'timezone' => $schedule->timezone,
                    'active' => $schedule->active,
                    'next_run_at' => $schedule->next_run_at->toIso8601String(),
                    'last_dispatched_at' => $schedule->last_dispatched_at?->toIso8601String(),
                    'last_error' => $schedule->last_error,
                    'last_run' => $schedule->lastRun ? [
                        'public_id' => $schedule->lastRun->public_id,
                        'name' => $schedule->lastRun->name,
                        'status' => $schedule->lastRun->status,
                    ] : null,
                ]),
        ]);
    }
}
