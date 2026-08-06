<?php

namespace App\Services\Discovery;

use App\Models\DiscoveredHost;
use App\Models\DiscoveryAllowedNetwork;
use App\Models\DiscoveryRun;
use App\Models\System;
use App\Models\User;
use App\Support\RegistryAudit;

/**
 * Dünner, domänenspezifischer Wrapper um App\Support\RegistryAudit -
 * Discovery-Ereignisse landen in derselben security_events-Tabelle wie
 * alle anderen Audit-Einträge der Anwendung (Abschnitt 16).
 */
final class DiscoveryAuditService
{
    public function __construct(private readonly RegistryAudit $audit) {}

    public function runStarted(DiscoveryRun $run, ?User $actor): void
    {
        $this->audit->record('discovery.run.started', $run, $actor, [
            'ip_range' => $run->ip_range,
            'total_ips' => $run->total_ips,
            'location' => $run->location,
            'department' => $run->department,
        ]);
    }

    public function runCancelled(DiscoveryRun $run, ?User $actor): void
    {
        $this->audit->record('discovery.run.cancelled', $run, $actor, [
            'processed_ips' => $run->processed_ips,
            'total_ips' => $run->total_ips,
        ]);
    }

    public function runCompleted(DiscoveryRun $run): void
    {
        $this->audit->record('discovery.run.completed', $run, null, [
            'status' => $run->status,
            'found_hosts_count' => $run->found_hosts_count,
            'dicom_candidates_count' => $run->dicom_candidates_count,
        ]);
    }

    public function findingConfirmed(DiscoveredHost $host, ?User $actor): void
    {
        $this->audit->record('discovery.finding.confirmed', $host, $actor, [
            'ip_address' => $host->ip_address,
            'confidence_score' => $host->confidence_score,
        ]);
    }

    public function findingIgnored(DiscoveredHost $host, ?User $actor): void
    {
        $this->audit->record('discovery.finding.ignored', $host, $actor, [
            'ip_address' => $host->ip_address,
        ]);
    }

    public function systemPromoted(System $system, DiscoveredHost $host, ?User $actor): void
    {
        $this->audit->record('discovery.system.promoted', $system, $actor, [
            'discovered_host_public_id' => $host->public_id,
            'discovery_run_id' => $host->discovery_run_id,
            'source_ip' => $host->ip_address,
        ]);
    }

    public function allowedNetworkChanged(string $action, DiscoveryAllowedNetwork $network, ?User $actor): void
    {
        $this->audit->record("discovery.allowed_network.{$action}", $network, $actor, [
            'cidr' => $network->cidr,
            'active' => $network->active,
        ]);
    }
}
