<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSystemReferenceRequest;
use App\Http\Requests\UpdateSystemReferenceRequest;
use App\Models\SystemStatus;
use App\Models\SystemType;
use App\Models\User;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;

final class SettingsSystemReferenceController extends Controller
{
    public function storeType(StoreSystemReferenceRequest $request, RegistryAudit $audit): RedirectResponse
    {
        return $this->store($request, new SystemType, $audit, 'Systemtyp');
    }

    public function updateType(
        UpdateSystemReferenceRequest $request,
        SystemType $systemType,
        RegistryAudit $audit,
    ): RedirectResponse {
        return $this->update($request, $systemType, $audit, 'Systemtyp');
    }

    public function storeStatus(StoreSystemReferenceRequest $request, RegistryAudit $audit): RedirectResponse
    {
        return $this->store($request, new SystemStatus, $audit, 'Systemstatus');
    }

    public function updateStatus(
        UpdateSystemReferenceRequest $request,
        SystemStatus $systemStatus,
        RegistryAudit $audit,
    ): RedirectResponse {
        return $this->update($request, $systemStatus, $audit, 'Systemstatus');
    }

    private function store(
        StoreSystemReferenceRequest $request,
        SystemType|SystemStatus $reference,
        RegistryAudit $audit,
        string $label,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        $reference->fill($request->validated())->save();
        $kind = $reference instanceof SystemType ? 'system_type' : 'system_status';
        $audit->record("settings.{$kind}.created", $reference, $actor, [
            'after' => $reference->only(['code', 'label', 'sort_order', 'is_active']),
        ]);

        return back()->with('success', "{$label} wurde angelegt.");
    }

    private function update(
        UpdateSystemReferenceRequest $request,
        SystemType|SystemStatus $reference,
        RegistryAudit $audit,
        string $label,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        $before = $reference->only(['code', 'label', 'sort_order', 'is_active']);
        $reference->update($request->validated());
        $after = $reference->only(['code', 'label', 'sort_order', 'is_active']);
        $kind = $reference instanceof SystemType ? 'system_type' : 'system_status';
        $audit->record("settings.{$kind}.updated", $reference, $actor, [
            'before' => $before,
            'after' => $after,
            'changed_fields' => array_keys(array_diff_assoc($after, $before)),
        ]);

        return back()->with('success', "{$label} wurde aktualisiert.");
    }
}
