<?php

namespace App\Services\Reports;

use App\Models\DicomConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class FirewallMatrixQuery
{
    /** @param array<string,mixed> $filters
     * @return Collection<int,array<string,mixed>>
     */
    public function rows(array $filters): Collection
    {
        /** @var Collection<int, array<string, mixed>> $rows */
        $rows = $this->query($filters)->get()->map(static fn (DicomConnection $connection): array => [
            'public_id' => $connection->public_id,
            'name' => $connection->name,
            'source_system' => $connection->sourceNode->system->name,
            'source_node' => $connection->sourceNode->name,
            'source_host' => $connection->sourceNode->host,
            'source_ae_title' => $connection->calling_ae_title ?: $connection->sourceNode->ae_title,
            'target_system' => $connection->targetNode->system->name,
            'target_node' => $connection->targetNode->name,
            'target_host' => $connection->targetNode->host,
            'target_ae_title' => $connection->called_ae_title ?: $connection->targetNode->ae_title,
            'port' => $connection->port_override ?? $connection->targetNode->port,
            'service' => $connection->service,
            'tls_enabled' => $connection->tls_enabled,
            'source_organization' => $connection->sourceNode->system->organization?->name,
            'source_site' => $connection->sourceNode->system->site?->name,
            'source_department' => $connection->sourceNode->system->department?->name,
            'target_organization' => $connection->targetNode->system->organization?->name,
            'target_site' => $connection->targetNode->system->site?->name,
            'target_department' => $connection->targetNode->system->department?->name,
        ]);

        return $rows;
    }

    /** @param array<string,mixed> $filters
     * @return Builder<DicomConnection>
     */
    private function query(array $filters): Builder
    {
        return DicomConnection::query()->active()->where('status', 'active')->with([
            'sourceNode.system.organization:id,public_id,name', 'sourceNode.system.site:id,public_id,name', 'sourceNode.system.department:id,public_id,name',
            'targetNode.system.organization:id,public_id,name', 'targetNode.system.site:id,public_id,name', 'targetNode.system.department:id,public_id,name',
        ])->when(isset($filters['system']), fn ($query) => $query->where(fn ($nested) => $nested->whereHas('sourceNode.system', fn ($system) => $system->where('public_id', $filters['system']))->orWhereHas('targetNode.system', fn ($system) => $system->where('public_id', $filters['system']))))
            ->when(isset($filters['organization']), fn ($query) => $query->where(fn ($nested) => $nested->whereHas('sourceNode.system.organization', fn ($organization) => $organization->where('public_id', $filters['organization']))->orWhereHas('targetNode.system.organization', fn ($organization) => $organization->where('public_id', $filters['organization']))))
            ->when(isset($filters['site']), fn ($query) => $query->where(fn ($nested) => $nested->whereHas('sourceNode.system.site', fn ($site) => $site->where('public_id', $filters['site']))->orWhereHas('targetNode.system.site', fn ($site) => $site->where('public_id', $filters['site']))))
            ->when(isset($filters['department']), fn ($query) => $query->where(fn ($nested) => $nested->whereHas('sourceNode.system.department', fn ($department) => $department->where('public_id', $filters['department']))->orWhereHas('targetNode.system.department', fn ($department) => $department->where('public_id', $filters['department']))))
            ->when(isset($filters['service']), fn ($query) => $query->where('service', $filters['service']))
            ->orderBy('name');
    }
}
