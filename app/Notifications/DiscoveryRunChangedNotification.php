<?php

namespace App\Notifications;

use App\Models\DiscoveryRun;
use App\Models\DiscoverySchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class DiscoveryRunChangedNotification extends Notification
{
    use Queueable;

    /**
     * @param  list<string>  $newHostIps
     * @param  list<string>  $missingHostIps
     */
    public function __construct(
        private readonly DiscoveryRun $currentRun,
        private readonly DiscoveryRun $baselineRun,
        private readonly DiscoverySchedule $schedule,
        private readonly array $newHostIps,
        private readonly array $missingHostIps,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, int|string|list<string>> */
    public function toDatabase(object $notifiable): array
    {
        $newCount = count($this->newHostIps);
        $missingCount = count($this->missingHostIps);

        return [
            'kind' => 'discovery_run_changes',
            'title' => 'Änderungen im Discovery-Zeitplan erkannt',
            'schedule_public_id' => $this->schedule->public_id,
            'schedule_name' => $this->schedule->name,
            'current_run_public_id' => $this->currentRun->public_id,
            'current_run_name' => $this->currentRun->name,
            'baseline_run_public_id' => $this->baselineRun->public_id,
            'baseline_run_name' => $this->baselineRun->name,
            'new_hosts_count' => $newCount,
            'missing_hosts_count' => $missingCount,
            'new_host_ips' => array_slice($this->newHostIps, 0, 10),
            'missing_host_ips' => array_slice($this->missingHostIps, 0, 10),
            'url' => '/discovery/runs/'.$this->currentRun->public_id.'/compare/'.$this->baselineRun->public_id,
        ];
    }
}
