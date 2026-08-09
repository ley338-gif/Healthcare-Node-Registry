<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\Controller;
use App\Models\DiscoveryAllowedNetwork;
use App\Models\DiscoveryPortProfile;
use App\Services\Discovery\DiscoveryAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

final class DiscoveryPortProfileController extends Controller
{
    public function store(Request $request, DiscoveryAuditService $audit): RedirectResponse
    {
        Gate::authorize('create', DiscoveryAllowedNetwork::class);
        $data = $this->validated($request);

        $profile = DiscoveryPortProfile::query()->create([
            ...$data,
            'active' => true,
            'is_default' => false,
            'created_by' => $request->user()?->id,
        ]);
        $audit->portProfileChanged('created', $profile, $request->user());

        return back()->with('success', "Portprofil {$profile->name} wurde angelegt.");
    }

    public function update(Request $request, DiscoveryPortProfile $discoveryPortProfile, DiscoveryAuditService $audit): RedirectResponse
    {
        Gate::authorize('create', DiscoveryAllowedNetwork::class);
        $data = $this->validated($request, $discoveryPortProfile);

        DB::transaction(function () use ($data, $discoveryPortProfile): void {
            if (($data['is_default'] ?? false) === true) {
                DiscoveryPortProfile::query()->whereKeyNot($discoveryPortProfile->id)->update(['is_default' => false]);
            }
            $discoveryPortProfile->update($data);
        });
        $audit->portProfileChanged('updated', $discoveryPortProfile->refresh(), $request->user());

        return back()->with('success', 'Portprofil wurde aktualisiert.');
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, ?DiscoveryPortProfile $profile = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:160', Rule::unique('discovery_port_profiles', 'name')->ignore($profile?->id)],
            'description' => ['nullable', 'string', 'max:255'],
            'active' => [$profile === null ? 'sometimes' : 'required', 'boolean'],
            'is_default' => [$profile === null ? 'sometimes' : 'required', 'boolean'],
            'ports' => ['required', 'array', 'min:1', 'max:40'],
            'ports.*.port' => ['required', 'integer', 'between:1,65535', 'distinct'],
            'ports.*.protocol' => ['required', Rule::in(['tcp'])],
            'ports.*.label' => ['nullable', 'string', 'max:120'],
            'ports.*.is_dicom_candidate' => ['required', 'boolean'],
            'ports.*.enabled' => ['required', 'boolean'],
        ]);
    }
}
