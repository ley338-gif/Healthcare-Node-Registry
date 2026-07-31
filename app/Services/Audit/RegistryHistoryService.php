<?php

namespace App\Services\Audit;

use App\Models\Department;
use App\Models\DicomConnection;
use App\Models\DicomNode;
use App\Models\Organization;
use App\Models\SecurityEvent;
use App\Models\Site;
use App\Models\System;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

final class RegistryHistoryService
{
    /** @return Builder<SecurityEvent> */
    public function forContext(User $user, Model $context, bool $includeDescendants = true): Builder
    {
        Gate::forUser($user)->authorize('view', $context);
        $subjects = $this->subjects($context, $includeDescendants);

        return SecurityEvent::query()
            ->where(function (Builder $query) use ($subjects): void {
                foreach ($subjects as $type => $publicIds) {
                    $query->orWhere(function (Builder $subjectQuery) use ($type, $publicIds): void {
                        $subjectQuery->where('subject_type', $type)->whereIn('subject_public_id', $publicIds);
                    });
                }
            })
            ->latest('occurred_at');
    }

    /**
     * Base query for the future global audit explorer.
     *
     * @return Builder<SecurityEvent>
     */
    public function global(User $user): Builder
    {
        abort_unless($user->hasPermission('audit.view'), 403);

        return SecurityEvent::query()->latest('occurred_at');
    }

    /** @return array<class-string<Model>, list<string>> */
    private function subjects(Model $context, bool $includeDescendants): array
    {
        $subjects = [$context::class => [(string) $context->getAttribute('public_id')]];
        if (! $includeDescendants) {
            return $subjects;
        }

        return match (true) {
            $context instanceof Organization => $this->organizationSubjects($context, $subjects),
            $context instanceof Site => $this->siteSubjects($context, $subjects),
            $context instanceof Department => $this->departmentSubjects($context, $subjects),
            $context instanceof System => $this->systemSubjects($context, $subjects),
            $context instanceof DicomNode => $this->nodeSubjects($context, $subjects),
            $context instanceof DicomConnection => $subjects,
            default => throw new InvalidArgumentException('Unsupported registry history context: '.$context::class),
        };
    }

    /**
     * @param  array<class-string<Model>, list<string>>  $subjects
     * @return array<class-string<Model>, list<string>>
     */
    private function organizationSubjects(Organization $organization, array $subjects): array
    {
        $siteIds = Site::query()->where('organization_id', $organization->id)->pluck('id');
        $departmentIds = Department::query()->whereIn('site_id', $siteIds)->pluck('id');
        $systems = System::query()->where('organization_id', $organization->id)
            ->orWhereIn('site_id', $siteIds)->orWhereIn('department_id', $departmentIds)->get(['id', 'public_id']);
        $subjects[Site::class] = Site::query()->whereIn('id', $siteIds)->pluck('public_id')->all();
        $subjects[Department::class] = Department::query()->whereIn('id', $departmentIds)->pluck('public_id')->all();

        return $this->appendSystems($subjects, $systems);
    }

    /**
     * @param  array<class-string<Model>, list<string>>  $subjects
     * @return array<class-string<Model>, list<string>>
     */
    private function siteSubjects(Site $site, array $subjects): array
    {
        $departmentIds = Department::query()->where('site_id', $site->id)->pluck('id');
        $subjects[Department::class] = Department::query()->whereIn('id', $departmentIds)->pluck('public_id')->all();
        $systems = System::query()->where('site_id', $site->id)->orWhereIn('department_id', $departmentIds)->get(['id', 'public_id']);

        return $this->appendSystems($subjects, $systems);
    }

    /**
     * @param  array<class-string<Model>, list<string>>  $subjects
     * @return array<class-string<Model>, list<string>>
     */
    private function departmentSubjects(Department $department, array $subjects): array
    {
        return $this->appendSystems($subjects, System::query()->where('department_id', $department->id)->get(['id', 'public_id']));
    }

    /**
     * @param  array<class-string<Model>, list<string>>  $subjects
     * @param  Collection<int, System>  $systems
     * @return array<class-string<Model>, list<string>>
     */
    private function appendSystems(array $subjects, $systems): array
    {
        $subjects[System::class] = $systems->pluck('public_id')->all();
        $nodeModels = DicomNode::query()->whereIn('system_id', $systems->pluck('id'))->get(['id', 'public_id']);
        $subjects[DicomNode::class] = $nodeModels->pluck('public_id')->all();
        $subjects[DicomConnection::class] = $this->connectionPublicIds($nodeModels->pluck('id')->all());

        return $subjects;
    }

    /**
     * @param  array<class-string<Model>, list<string>>  $subjects
     * @return array<class-string<Model>, list<string>>
     */
    private function systemSubjects(System $system, array $subjects): array
    {
        $nodeModels = DicomNode::query()->where('system_id', $system->id)->get(['id', 'public_id']);
        $subjects[DicomNode::class] = $nodeModels->pluck('public_id')->all();
        $subjects[DicomConnection::class] = $this->connectionPublicIds($nodeModels->pluck('id')->all());

        return $subjects;
    }

    /**
     * @param  array<class-string<Model>, list<string>>  $subjects
     * @return array<class-string<Model>, list<string>>
     */
    private function nodeSubjects(DicomNode $node, array $subjects): array
    {
        $subjects[DicomConnection::class] = $this->connectionPublicIds([$node->id]);

        return $subjects;
    }

    /**
     * @param  list<int>  $nodeIds
     * @return list<string>
     */
    private function connectionPublicIds(array $nodeIds): array
    {
        if ($nodeIds === []) {
            return [];
        }

        return DicomConnection::query()
            ->where(function (Builder $query) use ($nodeIds): void {
                $query->whereIn('source_dicom_node_id', $nodeIds)
                    ->orWhereIn('target_dicom_node_id', $nodeIds)
                    ->orWhereIn('destination_dicom_node_id', $nodeIds);
            })
            ->pluck('public_id')
            ->all();
    }
}
