<?php

namespace App\Services\Discovery;

use App\Models\DiscoveryRun;
use App\Models\DiscoverySchedule;
use App\Models\User;
use App\Notifications\DiscoveryRunChangedNotification;
use Illuminate\Database\Eloquent\Collection;

final class DiscoveryChangeNotificationService
{
    public function __construct(private readonly DiscoveryRunComparisonService $comparisonService) {}

    /**
     * @return array{baseline_run_id: int|null, new: int, missing: int, recipients: int, sent: int, skipped: int}
     */
    public function notifyForCompletedRun(DiscoveryRun $run): array
    {
        $empty = ['baseline_run_id' => null, 'new' => 0, 'missing' => 0, 'recipients' => 0, 'sent' => 0, 'skipped' => 0];
        if ($run->status !== DiscoveryRun::STATUS_COMPLETED || $run->discovery_schedule_id === null || $run->finished_at === null) {
            return $empty;
        }

        $baseline = DiscoveryRun::query()
            ->where('discovery_schedule_id', $run->discovery_schedule_id)
            ->whereKeyNot($run->id)
            ->where('status', DiscoveryRun::STATUS_COMPLETED)
            ->where('ip_range', $run->ip_range)
            ->where('finished_at', '<', $run->finished_at)
            ->latest('finished_at')
            ->first();
        if (! $baseline instanceof DiscoveryRun) {
            return $empty;
        }

        $comparison = $this->comparisonService->compare($baseline, $run);
        $newCount = $comparison['summary']['new'];
        $missingCount = $comparison['summary']['missing'];
        $result = [...$empty, 'baseline_run_id' => $baseline->id, 'new' => $newCount, 'missing' => $missingCount];
        if ($newCount === 0 && $missingCount === 0) {
            return $result;
        }

        $schedule = DiscoverySchedule::withTrashed()->find($run->discovery_schedule_id);
        if (! $schedule instanceof DiscoverySchedule) {
            return $result;
        }

        /** @var list<string> $newHostIps */
        $newHostIps = collect($comparison['results'])
            ->where('change_type', 'new')
            ->pluck('ip_address')
            ->filter(fn (mixed $value): bool => is_string($value))
            ->values()
            ->all();
        /** @var list<string> $missingHostIps */
        $missingHostIps = collect($comparison['results'])
            ->where('change_type', 'missing')
            ->pluck('ip_address')
            ->filter(fn (mixed $value): bool => is_string($value))
            ->values()
            ->all();

        /** @var Collection<int, User> $recipients */
        $recipients = User::query()
            ->where('is_active', true)
            ->whereHas('roles.permissions', fn ($query) => $query->whereIn('name', ['discovery.view', 'discovery.run', 'discovery.manage']))
            ->get();
        $result['recipients'] = $recipients->count();

        foreach ($recipients as $recipient) {
            $alreadyNotified = $recipient->notifications()
                ->where('type', DiscoveryRunChangedNotification::class)
                ->where('data->current_run_public_id', $run->public_id)
                ->exists();
            if ($alreadyNotified) {
                $result['skipped']++;

                continue;
            }

            $recipient->notify(new DiscoveryRunChangedNotification($run, $baseline, $schedule, $newHostIps, $missingHostIps));
            $result['sent']++;
        }

        return $result;
    }
}
