<?php

namespace App\Services\Search;

use App\Models\Department;
use App\Models\DiagnosticTestProfile;
use App\Models\DiagnosticTestRun;
use App\Models\DicomConnection;
use App\Models\DicomNode;
use App\Models\Organization;
use App\Models\RegistryDocument;
use App\Models\Site;
use App\Models\System;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class GlobalSearchService
{
    private const LIMIT_PER_GROUP = 5;

    /** @return list<array{group: string, type: string, title: string, subtitle: string, url: string}> */
    public function search(User $user, string $term): array
    {
        $results = collect();
        $pattern = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';

        if ($user->can('viewAny', System::class)) {
            $results = $results
                ->concat($this->organizations($pattern))
                ->concat($this->sites($pattern))
                ->concat($this->departments($pattern))
                ->concat($this->systems($pattern))
                ->concat($this->dicomNodes($pattern))
                ->concat($this->dicomConnections($pattern))
                ->concat($this->testRuns($pattern))
                ->concat($this->testProfiles($pattern));
        }

        if ($user->hasPermission('documents.view')) {
            $results = $results->concat($this->documents($pattern));
        }

        if ($user->can('viewAny', User::class)) {
            $results = $results->concat($this->users($pattern));
        }

        return $results->values()->all();
    }

    /** @return Collection<int, array{group: string, type: string, title: string, subtitle: string, url: string}> */
    private function organizations(string $pattern): Collection
    {
        return Organization::query()->active()->where(fn (Builder $query) => $query->where('name', 'ilike', $pattern)->orWhere('short_name', 'ilike', $pattern))
            ->limit(self::LIMIT_PER_GROUP)->get()->map(fn (Organization $item): array => $this->result('Registry', 'Organisation', $item->name, $item->short_name ?? 'Organisation', "/structure?selected_type=organization&selected_id={$item->public_id}"));
    }

    /** @return Collection<int, array{group: string, type: string, title: string, subtitle: string, url: string}> */
    private function sites(string $pattern): Collection
    {
        return Site::query()->active()->with('organization:id,name')->where(fn (Builder $query) => $query->where('name', 'ilike', $pattern)->orWhere('code', 'ilike', $pattern)->orWhere('city', 'ilike', $pattern))
            ->limit(self::LIMIT_PER_GROUP)->get()->map(fn (Site $item): array => $this->result('Registry', 'Standort', $item->name, collect([$item->organization->name, $item->city])->filter()->join(' · '), "/structure?selected_type=site&selected_id={$item->public_id}"));
    }

    /** @return Collection<int, array{group: string, type: string, title: string, subtitle: string, url: string}> */
    private function departments(string $pattern): Collection
    {
        return Department::query()->active()->with('site:id,name')->where(fn (Builder $query) => $query->where('name', 'ilike', $pattern)->orWhere('code', 'ilike', $pattern)->orWhere('specialty', 'ilike', $pattern))
            ->limit(self::LIMIT_PER_GROUP)->get()->map(fn (Department $item): array => $this->result('Registry', 'Abteilung', $item->name, collect([$item->site->name, $item->specialty])->filter()->join(' · '), "/structure?selected_type=department&selected_id={$item->public_id}"));
    }

    /** @return Collection<int, array{group: string, type: string, title: string, subtitle: string, url: string}> */
    private function systems(string $pattern): Collection
    {
        return System::query()->active()->where(fn (Builder $query) => $query->where('name', 'ilike', $pattern)->orWhere('hostname', 'ilike', $pattern)->orWhere('fqdn', 'ilike', $pattern)->orWhere('ip_address', 'ilike', $pattern)->orWhereHas('networkInterfaces', fn (Builder $interfaces) => $interfaces->where('hostname', 'ilike', $pattern)->orWhere('fqdn', 'ilike', $pattern)->orWhere('ip_address', 'ilike', $pattern)->orWhere('interface_label', 'ilike', $pattern))->orWhere('vendor', 'ilike', $pattern)->orWhere('product', 'ilike', $pattern)->orWhere('inventory_number', 'ilike', $pattern)->orWhere('serial_number', 'ilike', $pattern))
            ->limit(self::LIMIT_PER_GROUP)->get()->map(fn (System $item): array => $this->result('Registry', 'System', $item->name, collect([$item->system_type, $item->hostname ?? $item->ip_address])->filter()->join(' · '), "/systems/{$item->public_id}"));
    }

    /** @return Collection<int, array{group: string, type: string, title: string, subtitle: string, url: string}> */
    private function dicomNodes(string $pattern): Collection
    {
        return DicomNode::query()->active()->with('system:id,name,archived_at')->whereHas('system', fn (Builder $query) => $query->whereNull('archived_at'))->where(fn (Builder $query) => $query->where('name', 'ilike', $pattern)->orWhere('ae_title', 'ilike', $pattern)->orWhere('host', 'ilike', $pattern))
            ->limit(self::LIMIT_PER_GROUP)->get()->map(fn (DicomNode $item): array => $this->result('DICOM', 'DICOM-Knoten', $item->name, "{$item->ae_title} · {$item->host}:{$item->port} · {$item->system->name}", "/network?node={$item->public_id}"));
    }

    /** @return Collection<int, array{group: string, type: string, title: string, subtitle: string, url: string}> */
    private function dicomConnections(string $pattern): Collection
    {
        return DicomConnection::query()->active()->with(['sourceNode:id,name', 'targetNode:id,name'])->where(fn (Builder $query) => $query->where('name', 'ilike', $pattern)->orWhere('service', 'ilike', $pattern)->orWhere('calling_ae_title', 'ilike', $pattern)->orWhere('called_ae_title', 'ilike', $pattern))
            ->limit(self::LIMIT_PER_GROUP)->get()->map(fn (DicomConnection $item): array => $this->result('DICOM', 'DICOM-Verbindung', $item->name, "{$item->sourceNode->name} → {$item->targetNode->name} · {$item->service}", "/network?connection={$item->public_id}"));
    }

    /** @return Collection<int, array{group: string, type: string, title: string, subtitle: string, url: string}> */
    private function documents(string $pattern): Collection
    {
        return RegistryDocument::query()->whereNull('archived_at')->where('status', '!=', 'archived')->where(fn (Builder $query) => $query->where('title', 'ilike', $pattern)->orWhere('description', 'ilike', $pattern)->orWhere('contract_reference', 'ilike', $pattern)->orWhereRaw('tags::text ilike ?', [$pattern]))
            ->limit(self::LIMIT_PER_GROUP)->get()->map(fn (RegistryDocument $item): array => $this->result('Dokumente', 'Dokument', $item->title, $item->category->label(), "/documents?document={$item->public_id}"));
    }

    /** @return Collection<int, array{group: string, type: string, title: string, subtitle: string, url: string}> */
    private function testRuns(string $pattern): Collection
    {
        return DiagnosticTestRun::query()->with(['dicomNode:id,name', 'system:id,name'])->where(fn (Builder $query) => $query->where('summary', 'ilike', $pattern)->orWhere('test_type', 'ilike', $pattern)->orWhere('status', 'ilike', $pattern)->orWhere('target_host', 'ilike', $pattern)->orWhere('calling_ae_title', 'ilike', $pattern)->orWhere('called_ae_title', 'ilike', $pattern))
            ->latest('started_at')->limit(self::LIMIT_PER_GROUP)->get()->map(fn (DiagnosticTestRun $item): array => $this->result('Tests', 'Testergebnis', $item->summary, "{$item->test_type} · {$item->status} · {$item->dicomNode->name}", "/tests?run={$item->public_id}"));
    }

    /** @return Collection<int, array{group: string, type: string, title: string, subtitle: string, url: string}> */
    private function testProfiles(string $pattern): Collection
    {
        return DiagnosticTestProfile::query()->whereNull('archived_at')->with('dicomNode:id,public_id,name')->where(fn (Builder $query) => $query->where('name', 'ilike', $pattern)->orWhere('description', 'ilike', $pattern)->orWhere('test_type', 'ilike', $pattern))
            ->limit(self::LIMIT_PER_GROUP)->get()->map(fn (DiagnosticTestProfile $item): array => $this->result('Tests', 'Testprofil', $item->name, "{$item->test_type} · {$item->dicomNode->name}", "/tests?history_node={$item->dicomNode->public_id}"));
    }

    /** @return Collection<int, array{group: string, type: string, title: string, subtitle: string, url: string}> */
    private function users(string $pattern): Collection
    {
        return User::query()->where(fn (Builder $query) => $query->where('name', 'ilike', $pattern)->orWhere('email', 'ilike', $pattern))
            ->limit(self::LIMIT_PER_GROUP)->get()->map(fn (User $item): array => $this->result('Administration', 'Benutzer', $item->name, $item->email, '/settings?search='.urlencode($item->email)));
    }

    /** @return array{group: string, type: string, title: string, subtitle: string, url: string} */
    private function result(string $group, string $type, string $title, string $subtitle, string $url): array
    {
        return compact('group', 'type', 'title', 'subtitle', 'url');
    }
}
