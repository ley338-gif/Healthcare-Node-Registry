<?php

namespace App\Services\Documents;

use App\Models\Department;
use App\Models\Organization;
use App\Models\RegistryDocument;
use App\Models\Site;
use App\Models\System;
use App\Models\User;
use App\Support\RegistryDocumentCategory;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class RegistryDocumentQueryService
{
    /** @var list<string> */
    public const FILTER_KEYS = [
        'document_search', 'document_category', 'document_file_type', 'document_status',
        'document_validity', 'document_uploader', 'document_from', 'document_to', 'document_scan_status',
    ];

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, RegistryDocument>
     */
    public function paginate(Organization|Site|Department|System $context, array $filters): LengthAwarePaginator
    {
        $query = $context->documents()->getQuery()->with([
            'currentVersion.uploadedByUser:id,public_id,name',
            'versions.uploadedByUser:id,public_id,name',
        ]);
        $search = trim((string) ($filters['document_search'] ?? ''));
        $categoryMatches = collect(RegistryDocumentCategory::cases())
            ->filter(fn (RegistryDocumentCategory $category): bool => $search !== '' && str_contains(mb_strtolower($category->label()), mb_strtolower($search)))
            ->map(fn (RegistryDocumentCategory $category): string => $category->value)
            ->all();

        $query
            ->when($search !== '', fn (Builder $builder) => $builder->where(function (Builder $searchQuery) use ($search, $categoryMatches): void {
                $searchQuery->where('title', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhere('contract_reference', 'ilike', "%{$search}%")
                    ->orWhereRaw('tags::text ilike ?', ["%{$search}%"])
                    ->orWhereHas('versions', fn (Builder $versionQuery) => $versionQuery->where('original_filename', 'ilike', "%{$search}%"));
                if ($categoryMatches !== []) {
                    $searchQuery->orWhereIn('category', $categoryMatches);
                }
            }))
            ->when($this->value($filters, 'document_category') !== '', fn (Builder $builder) => $builder->where('category', $this->value($filters, 'document_category')))
            ->when($this->value($filters, 'document_file_type') !== '', fn (Builder $builder) => $builder->whereHas('currentVersion', fn (Builder $versionQuery) => $versionQuery->where('file_extension', $this->value($filters, 'document_file_type'))))
            ->when($this->value($filters, 'document_status') !== '', fn (Builder $builder) => $builder->where('status', $this->value($filters, 'document_status')))
            ->when($this->value($filters, 'document_scan_status') !== '', fn (Builder $builder) => $builder->whereHas('currentVersion', fn (Builder $versionQuery) => $versionQuery->where('malware_scan_status', $this->value($filters, 'document_scan_status'))))
            ->when($this->value($filters, 'document_uploader') !== '', fn (Builder $builder) => $builder->whereHas('currentVersion.uploadedByUser', fn (Builder $userQuery) => $userQuery->where('public_id', $this->value($filters, 'document_uploader'))))
            ->when($this->value($filters, 'document_from') !== '', fn (Builder $builder) => $builder->whereHas('currentVersion', fn (Builder $versionQuery) => $versionQuery->whereDate('uploaded_at', '>=', $this->value($filters, 'document_from'))))
            ->when($this->value($filters, 'document_to') !== '', fn (Builder $builder) => $builder->whereHas('currentVersion', fn (Builder $versionQuery) => $versionQuery->whereDate('uploaded_at', '<=', $this->value($filters, 'document_to'))));

        $this->applyValidity($query, $this->value($filters, 'document_validity'));

        return $query->latest('updated_at')->paginate(10, ['*'], 'document_page')->withQueryString()->through(function (RegistryDocument $document): RegistryDocument {
            $document->setAttribute('category_label', $document->category->label());

            return $document;
        });
    }

    /** @return Collection<int, array{public_id: string, name: string}> */
    public function uploaders(Organization|Site|Department|System $context): Collection
    {
        return User::query()
            ->whereHas('uploadedRegistryDocumentVersions.document', fn (Builder $query) => $query
                ->where('documentable_type', $context::class)
                ->where('documentable_id', $context->getKey()))
            ->orderBy('name')
            ->get(['public_id', 'name'])
            ->map(fn (User $user): array => ['public_id' => $user->public_id, 'name' => $user->name]);
    }

    /** @param array<string, mixed> $filters */
    private function value(array $filters, string $key): string
    {
        return trim((string) ($filters[$key] ?? ''));
    }

    /** @param Builder<RegistryDocument> $query */
    private function applyValidity(Builder $query, string $status): void
    {
        $today = CarbonImmutable::today();
        $warningEnd = $today->addDays(max(0, (int) config('registry_documents.expiry_warning_days')));
        match ($status) {
            'archived' => $query->whereNotNull('archived_at'),
            'undated' => $query->whereNull('archived_at')->whereNull('valid_until'),
            'expired' => $query->whereNull('archived_at')->whereDate('valid_until', '<', $today),
            'expiring_soon' => $query->whereNull('archived_at')->whereBetween('valid_until', [$today, $warningEnd]),
            'active' => $query->whereNull('archived_at')->whereDate('valid_until', '>', $warningEnd),
            default => null,
        };
    }
}
