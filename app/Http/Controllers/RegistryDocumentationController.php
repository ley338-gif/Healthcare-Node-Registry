<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegistryDocumentationRequest;
use App\Models\Department;
use App\Models\Organization;
use App\Models\RegistryDocumentation;
use App\Models\Site;
use App\Models\System;
use App\Models\User;
use App\Support\RegistryAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class RegistryDocumentationController extends Controller
{
    public function index(Request $request, string $documentableType, string $documentable): JsonResponse
    {
        $context = $this->resolve($documentableType, $documentable);
        Gate::authorize('view', $context);

        return response()->json([
            'data' => $this->documentationQuery($context)
                ->with('updatedByUser:id,public_id,name')
                ->orderBy('documentation_type')
                ->orderBy('section')
                ->get(),
        ]);
    }

    public function store(
        RegistryDocumentationRequest $request,
        string $documentableType,
        string $documentable,
        RegistryAudit $audit,
    ): RedirectResponse {
        $context = $this->resolve($documentableType, $documentable);
        Gate::authorize('update', $context);
        $validated = $request->validated();
        abort_if(
            $this->documentationQuery($context)
                ->where('documentation_type', $validated['documentation_type'])
                ->where('section', $validated['section'])
                ->exists(),
            422,
            'Für diese Sektion existiert bereits eine Dokumentation.',
        );
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        DB::transaction(function () use ($context, $validated, $user, $audit): void {
            $documentation = $this->documentationQuery($context)->create([
                ...$validated,
                'structured_data' => $validated['structured_data'] ?? [],
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);
            $after = $this->auditValues($documentation->only($this->auditedFields()));
            $audit->record('documentation.updated', $context, $user, [
                'action' => 'created',
                'documentation_public_id' => $documentation->public_id,
                'section' => $documentation->section,
                'changed_fields' => array_keys($after),
                'before' => [],
                'after' => $after,
            ]);
        });

        return back()->with('success', 'Dokumentation wurde gespeichert.');
    }

    public function update(
        RegistryDocumentationRequest $request,
        RegistryDocumentation $registryDocumentation,
        RegistryAudit $audit,
    ): RedirectResponse {
        $context = $registryDocumentation->documentable;
        abort_unless($context instanceof Model, 404);
        Gate::authorize('update', $context);
        $validated = $request->validated();
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        DB::transaction(function () use ($registryDocumentation, $context, $validated, $user, $audit): void {
            $beforeRaw = $registryDocumentation->only($this->auditedFields());
            $registryDocumentation->update([...$validated, 'updated_by' => $user->id]);
            $afterRaw = $registryDocumentation->only($this->auditedFields());
            $changed = array_keys(array_filter(
                $afterRaw,
                fn (mixed $value, string $field): bool => $value !== $beforeRaw[$field],
                ARRAY_FILTER_USE_BOTH,
            ));
            $before = $this->auditValues(array_intersect_key($beforeRaw, array_flip($changed)));
            $after = $this->auditValues(array_intersect_key($afterRaw, array_flip($changed)));
            $audit->record('documentation.updated', $context, $user, [
                'action' => 'updated',
                'documentation_public_id' => $registryDocumentation->public_id,
                'section' => $registryDocumentation->section,
                'changed_fields' => $changed,
                'before' => $before,
                'after' => $after,
            ]);
        });

        return back()->with('success', 'Dokumentation wurde aktualisiert.');
    }

    private function resolve(string $type, string $publicId): Organization|Site|Department|System
    {
        $model = match ($type) {
            'organizations' => Organization::class,
            'sites' => Site::class,
            'departments' => Department::class,
            'systems' => System::class,
            default => abort(404),
        };

        return $model::query()->where('public_id', $publicId)->firstOrFail();
    }

    /**
     * @return MorphMany<RegistryDocumentation, Organization>|MorphMany<RegistryDocumentation, Site>|MorphMany<RegistryDocumentation, Department>|MorphMany<RegistryDocumentation, System>
     */
    private function documentationQuery(Organization|Site|Department|System $context): MorphMany
    {
        return $context->documentation();
    }

    /** @return list<string> */
    private function auditedFields(): array
    {
        return ['documentation_type', 'section', 'title', 'content', 'structured_data', 'visibility'];
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function auditValues(array $values): array
    {
        foreach (['content', 'structured_data'] as $sensitiveField) {
            if (! array_key_exists($sensitiveField, $values)) {
                continue;
            }
            $serialized = is_string($values[$sensitiveField])
                ? $values[$sensitiveField]
                : json_encode($values[$sensitiveField], JSON_THROW_ON_ERROR);
            $values[$sensitiveField] = [
                'length' => strlen($serialized),
                'sha256' => hash('sha256', $serialized),
            ];
        }

        return $values;
    }
}
