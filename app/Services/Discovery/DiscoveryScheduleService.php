<?php

namespace App\Services\Discovery;

use App\Models\DiscoverySchedule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DiscoveryScheduleService
{
    public function __construct(
        private readonly DiscoveryRunService $runService,
        private readonly NetworkRangeService $rangeService,
        private readonly DiscoveryAuditService $auditService,
    ) {}

    /** @param array<string, mixed> $data */
    public function create(array $data, User $actor): DiscoverySchedule
    {
        return DB::transaction(function () use ($data, $actor): DiscoverySchedule {
            /** @var array<string, mixed> $scheduleData */
            $scheduleData = $data['schedule'];
            $configuration = collect($data)->except(['confirmed', 'execution_mode', 'schedule'])->all();

            $schedule = DiscoverySchedule::query()->create([
                'name' => $data['name'],
                'frequency' => $scheduleData['frequency'],
                'scheduled_time' => $scheduleData['scheduled_time'],
                'day_of_week' => $scheduleData['frequency'] === DiscoverySchedule::FREQUENCY_WEEKLY ? $scheduleData['day_of_week'] : null,
                'timezone' => $scheduleData['timezone'],
                'configuration' => $configuration,
                'active' => true,
                'next_run_at' => $this->nextOccurrence(
                    $scheduleData['frequency'],
                    $scheduleData['scheduled_time'],
                    $scheduleData['timezone'],
                    $scheduleData['day_of_week'] ?? null,
                ),
                'created_by' => $actor->id,
            ]);

            $this->auditService->scheduleChanged('created', $schedule, $actor);

            return $schedule;
        });
    }

    public function setActive(DiscoverySchedule $schedule, bool $active, User $actor): void
    {
        DB::transaction(function () use ($schedule, $active, $actor): void {
            $schedule->update([
                'active' => $active,
                'last_error' => $active ? null : $schedule->last_error,
                'next_run_at' => $active ? $this->nextOccurrence($schedule->frequency, $schedule->scheduled_time, $schedule->timezone, $schedule->day_of_week) : $schedule->next_run_at,
            ]);
            $this->auditService->scheduleChanged($active ? 'activated' : 'deactivated', $schedule, $actor);
        });
    }

    public function delete(DiscoverySchedule $schedule, User $actor): void
    {
        DB::transaction(function () use ($schedule, $actor): void {
            $this->auditService->scheduleChanged('deleted', $schedule, $actor);
            $schedule->delete();
        });
    }

    public function dispatchDue(): int
    {
        $ids = DiscoverySchedule::query()
            ->where('active', true)
            ->where('next_run_at', '<=', now())
            ->orderBy('next_run_at')
            ->limit(100)
            ->pluck('id');
        $dispatched = 0;

        foreach ($ids as $id) {
            if ($this->dispatchOne((int) $id)) {
                $dispatched++;
            }
        }

        return $dispatched;
    }

    private function dispatchOne(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $schedule = DiscoverySchedule::query()->lockForUpdate()->find($id);
            if (! $schedule instanceof DiscoverySchedule || ! $schedule->active || $schedule->next_run_at->isFuture()) {
                return false;
            }

            $configuration = $schedule->configuration;
            $nextRunAt = $this->nextOccurrence($schedule->frequency, $schedule->scheduled_time, $schedule->timezone, $schedule->day_of_week);

            try {
                $this->rangeService->validate((string) $configuration['ip_range'], (array) ($configuration['exclude_ips'] ?? []));
                $overlapping = $this->rangeService->overlappingActiveRunNames((string) $configuration['ip_range']);
                if ($overlapping !== []) {
                    $schedule->update([
                        'next_run_at' => now()->addMinutes(5),
                        'last_error' => 'Verschoben: Überlappender aktiver Lauf '.implode(', ', $overlapping),
                    ]);
                    $this->auditService->scheduleChanged('postponed', $schedule, null);

                    return false;
                }

                $run = $this->runService->createRun([
                    ...$configuration,
                    'name' => $schedule->name.' · '.now()->setTimezone($schedule->timezone)->format('Y-m-d H:i'),
                    'created_by' => $schedule->created_by,
                    'discovery_schedule_id' => $schedule->id,
                ], null);
                $schedule->update([
                    'next_run_at' => $nextRunAt,
                    'last_dispatched_at' => now(),
                    'last_run_id' => $run->id,
                    'last_error' => null,
                ]);
                $this->auditService->scheduleChanged('dispatched', $schedule, null);

                return true;
            } catch (NetworkRangeException $exception) {
                $schedule->update(['active' => false, 'last_error' => $exception->getMessage()]);
                $this->auditService->scheduleChanged('disabled', $schedule, null);

                return false;
            } catch (Throwable $exception) {
                report($exception);
                $schedule->update(['next_run_at' => $nextRunAt, 'last_error' => 'Ausführung fehlgeschlagen.']);
                $this->auditService->scheduleChanged('failed', $schedule, null);

                return false;
            }
        });
    }

    private function nextOccurrence(string $frequency, string $time, string $timezone, ?int $dayOfWeek): CarbonImmutable
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));
        $candidate = CarbonImmutable::now($timezone)->setTime($hour, $minute);

        if ($frequency === DiscoverySchedule::FREQUENCY_WEEKLY) {
            $targetDay = $dayOfWeek ?? 1;
            $candidate = $candidate->addDays(($targetDay - $candidate->dayOfWeekIso + 7) % 7);
        }
        if (! $candidate->isFuture()) {
            $candidate = $frequency === DiscoverySchedule::FREQUENCY_WEEKLY ? $candidate->addWeek() : $candidate->addDay();
        }

        return $candidate->utc();
    }
}
