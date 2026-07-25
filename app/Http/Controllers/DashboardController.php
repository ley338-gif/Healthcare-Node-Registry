<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Organization;
use App\Models\SecurityEvent;
use App\Models\Site;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        /** @var Collection<int, SecurityEvent> $events */
        $events = SecurityEvent::query()
            ->where('event_type', 'like', 'registry.%')
            ->latest('occurred_at')
            ->limit(6)
            ->get();

        $recentChanges = $events
            ->map(static function (SecurityEvent $event): array {
                return [
                    'event_type' => $event->event_type,
                    'subject_type' => class_basename($event->subject_type ?? ''),
                    'subject_public_id' => $event->subject_public_id,
                    'occurred_at' => $event->occurred_at->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        return Inertia::render('Dashboard', [
            'summary' => [
                'organizations' => Organization::query()->active()->count(),
                'sites' => Site::query()->active()->count(),
                'departments' => Department::query()->active()->count(),
                'systems' => 0,
                'connections' => 0,
            ],
            'recentChanges' => $recentChanges,
            'moduleStatus' => [
                ['label' => 'Organisationsstruktur', 'status' => 'bereit'],
                ['label' => 'System Registry', 'status' => 'nächster Sprint'],
                ['label' => 'Verbindungen', 'status' => 'geplant'],
                ['label' => 'Topologie', 'status' => 'geplant'],
                ['label' => 'Monitoring', 'status' => 'später'],
            ],
        ]);
    }
}
