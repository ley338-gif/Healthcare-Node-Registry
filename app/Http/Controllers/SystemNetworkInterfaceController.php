<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSystemNetworkInterfaceRequest;
use App\Http\Requests\UpdateSystemNetworkInterfaceRequest;
use App\Models\System;
use App\Models\SystemNetworkInterface;
use App\Support\RegistryAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class SystemNetworkInterfaceController extends Controller
{
    public function store(StoreSystemNetworkInterfaceRequest $request, System $system, RegistryAudit $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $system, $audit): void {
            $values = $request->validated();
            $values['is_primary'] = $values['is_primary'] || ! $system->networkInterfaces()->exists();
            if ($values['is_primary']) {
                $system->networkInterfaces()->update(['is_primary' => false]);
            }
            $interface = $system->networkInterfaces()->create($values);
            if ($interface->is_primary) {
                $interface->syncLegacySystemFields();
            }
            $audit->record('registry.system_network_interface.created', $interface, $request->user(), ['system_public_id' => $system->public_id]);
        });

        return back()->with('success', 'Netzwerkinterface wurde angelegt.');
    }

    public function update(UpdateSystemNetworkInterfaceRequest $request, SystemNetworkInterface $systemNetworkInterface, RegistryAudit $audit): RedirectResponse
    {
        DB::transaction(function () use ($request, $systemNetworkInterface, $audit): void {
            $values = $request->validated();
            $before = $systemNetworkInterface->only(array_keys($values));
            if ($values['is_primary']) {
                $systemNetworkInterface->system->networkInterfaces()->whereKeyNot($systemNetworkInterface->id)->update(['is_primary' => false]);
            }
            $systemNetworkInterface->update($values);
            $primary = $this->ensurePrimary($systemNetworkInterface->system, $systemNetworkInterface);
            $primary->syncLegacySystemFields();
            $audit->record('registry.system_network_interface.updated', $systemNetworkInterface, $request->user(), [
                'before' => $before,
                'after' => $systemNetworkInterface->only(array_keys($values)),
                'system_public_id' => $systemNetworkInterface->system->public_id,
            ]);
        });

        return back()->with('success', 'Netzwerkinterface wurde aktualisiert.');
    }

    public function destroy(Request $request, SystemNetworkInterface $systemNetworkInterface, RegistryAudit $audit): RedirectResponse
    {
        Gate::authorize('update', $systemNetworkInterface->system);

        DB::transaction(function () use ($request, $systemNetworkInterface, $audit): void {
            $system = $systemNetworkInterface->system;
            $wasPrimary = $systemNetworkInterface->is_primary;
            $audit->record('registry.system_network_interface.deleted', $systemNetworkInterface, $request->user(), [
                'interface_label' => $systemNetworkInterface->interface_label,
                'system_public_id' => $system->public_id,
            ]);
            $systemNetworkInterface->delete();

            if (! $wasPrimary) {
                return;
            }
            $replacement = $system->networkInterfaces()->first();
            if ($replacement === null) {
                $system->updateQuietly(['hostname' => null, 'fqdn' => null, 'ip_address' => null]);

                return;
            }
            $replacement->update(['is_primary' => true]);
            $replacement->syncLegacySystemFields();
        });

        return back()->with('success', 'Netzwerkinterface wurde gelöscht.');
    }

    private function ensurePrimary(System $system, SystemNetworkInterface $preferred): SystemNetworkInterface
    {
        $primary = $system->networkInterfaces()->where('is_primary', true)->first();
        if ($primary !== null) {
            return $primary;
        }

        $fallback = $system->networkInterfaces()->whereKeyNot($preferred->id)->first() ?? $preferred;
        $fallback->update(['is_primary' => true]);

        return $fallback;
    }
}
