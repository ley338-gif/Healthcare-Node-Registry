<?php

namespace App\Http\Controllers;

use App\Models\SecurityEvent;
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
            ->map(static fn (SecurityEvent $event): array => [
                'event_type' => $event->event_type,
                'subject_type' => class_basename($event->subject_type ?? ''),
                'subject_public_id' => $event->subject_public_id,
                'occurred_at' => $event->occurred_at->toIso8601String(),
            ])
            ->values()
            ->all();

        return Inertia::render('Dashboard', [
            'summary' => [
                'systems' => 0,
                'connections' => 0,
                'online' => 0,
                'offline' => 0,
                'documents' => 0,
            ],
            'recentChanges' => $recentChanges,
        ]);
    }
}
