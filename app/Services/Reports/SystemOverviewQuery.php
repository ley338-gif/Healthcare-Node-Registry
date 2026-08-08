<?php

namespace App\Services\Reports;

use App\Models\System;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class SystemOverviewQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<System>
     */
    public function query(array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return System::query()
            ->with([
                'organization:id,public_id,name',
                'site:id,public_id,name',
                'department:id,public_id,name',
            ])
            ->withCount([
                'dicomNodes as dicom_nodes_count' => fn ($query) => $query->active(),
                'dicomNodes as failed_dicom_nodes_count' => fn ($query) => $query
                    ->active()
                    ->whereNotNull('last_verification_status')
                    ->where('last_verification_status', '!=', 'success'),
            ])
            ->whereNull('archived_at')
            ->when($search !== '', fn ($query) => $query->where(fn ($nested) => $nested
                ->where('name', 'ilike', "%{$search}%")
                ->orWhere('hostname', 'ilike', "%{$search}%")
                ->orWhere('fqdn', 'ilike', "%{$search}%")
                ->orWhere('ip_address', 'ilike', "%{$search}%")
                ->orWhereHas('networkInterfaces', fn ($interfaces) => $interfaces
                    ->where('interface_label', 'ilike', "%{$search}%")
                    ->orWhere('hostname', 'ilike', "%{$search}%")
                    ->orWhere('fqdn', 'ilike', "%{$search}%")
                    ->orWhere('ip_address', 'ilike', "%{$search}%"))
                ->orWhere('vendor', 'ilike', "%{$search}%")
                ->orWhere('product', 'ilike', "%{$search}%")))
            ->when(filled($filters['type'] ?? null), fn ($query) => $query->where('system_type', $filters['type']))
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('status', $filters['status']))
            ->when((int) ($filters['organization'] ?? 0) > 0, fn ($query) => $query->where('organization_id', $filters['organization']))
            ->when((int) ($filters['site'] ?? 0) > 0, fn ($query) => $query->where('site_id', $filters['site']))
            ->when((int) ($filters['department'] ?? 0) > 0, fn ($query) => $query->where('department_id', $filters['department']))
            ->orderBy('name');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(array $filters): Collection
    {
        /** @var Collection<int, array<string, mixed>> $rows */
        $rows = $this->query($filters)
            ->with([
                'dicomNodes' => fn ($query) => $query->active()->orderBy('name'),
                'networkInterfaces',
            ])
            ->get()
            ->toBase()
            ->flatMap(static function (System $system): array {
                $nodes = $system->dicomNodes->isEmpty() ? [null] : $system->dicomNodes->all();

                return array_map(static fn ($node): array => [
                    'organization' => $system->organization->name,
                    'site' => $system->site?->name,
                    'department' => $system->department?->name,
                    'system' => $system->name,
                    'system_type' => $system->system_type,
                    'system_status' => $system->status,
                    'hostname' => $system->hostname,
                    'fqdn' => $system->fqdn,
                    'ip_address' => $system->ip_address,
                    'network_interfaces' => $system->networkInterfaces->map(static fn ($interface): string => implode(' | ', array_filter([
                        $interface->interface_label,
                        $interface->hostname,
                        $interface->fqdn,
                        $interface->ip_address,
                    ])))->implode('; '),
                    'vendor' => $system->vendor,
                    'product' => $system->product,
                    'model' => $system->model,
                    'version' => $system->version,
                    'operating_system' => $system->operating_system,
                    'operating_system_version' => $system->operating_system_version,
                    'inventory_number' => $system->inventory_number,
                    'serial_number' => $system->serial_number,
                    'dicom_node' => $node?->name,
                    'ae_title' => $node?->ae_title,
                    'modality' => $node?->modality,
                    'dicom_host' => $node?->host,
                    'dicom_port' => $node?->port,
                    'dicom_role' => $node?->role,
                    'dicom_status' => $node?->status,
                    'dicom_tls' => $node?->tls_enabled,
                ], $nodes);
            })
            ->values();

        return $rows;
    }
}
