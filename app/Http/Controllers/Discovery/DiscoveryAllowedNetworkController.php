<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\Controller;
use App\Models\DiscoveryAllowedNetwork;
use App\Services\Discovery\DiscoveryAuditService;
use App\Services\Discovery\NetworkRangeException;
use App\Services\Discovery\NetworkRangeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class DiscoveryAllowedNetworkController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', DiscoveryAllowedNetwork::class);

        return Inertia::render('Settings/Discovery', [
            'networks' => DiscoveryAllowedNetwork::query()->with('creator:id,public_id,name')->orderBy('cidr')->get()->map(fn (DiscoveryAllowedNetwork $network) => [
                'public_id' => $network->public_id,
                'cidr' => $network->cidr,
                'description' => $network->description,
                'active' => $network->active,
                'creator' => $network->creator?->name,
                'created_at' => $network->created_at?->toIso8601String(),
            ]),
        ]);
    }

    public function store(Request $request, DiscoveryAuditService $audit): RedirectResponse
    {
        Gate::authorize('create', DiscoveryAllowedNetwork::class);

        $data = $request->validate([
            'cidr' => ['required', 'string', 'max:32', 'unique:discovery_allowed_networks,cidr'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $this->assertValidCidr($data['cidr']);

        $network = DiscoveryAllowedNetwork::query()->create([
            ...$data,
            'active' => true,
            'created_by' => $request->user()?->id,
        ]);

        $audit->allowedNetworkChanged('created', $network, $request->user());

        return back()->with('success', "Netzbereich {$network->cidr} wurde freigegeben.");
    }

    public function update(Request $request, DiscoveryAllowedNetwork $discoveryAllowedNetwork, DiscoveryAuditService $audit): RedirectResponse
    {
        Gate::authorize('update', $discoveryAllowedNetwork);

        $data = $request->validate([
            'active' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $discoveryAllowedNetwork->update($data);
        $audit->allowedNetworkChanged('updated', $discoveryAllowedNetwork, $request->user());

        return back()->with('success', 'Netzbereich wurde aktualisiert.');
    }

    public function destroy(Request $request, DiscoveryAllowedNetwork $discoveryAllowedNetwork, DiscoveryAuditService $audit): RedirectResponse
    {
        Gate::authorize('delete', $discoveryAllowedNetwork);

        $audit->allowedNetworkChanged('deleted', $discoveryAllowedNetwork, $request->user());
        $discoveryAllowedNetwork->delete();

        return back()->with('success', 'Netzbereich wurde entfernt.');
    }

    private function assertValidCidr(string $cidr): void
    {
        try {
            app(NetworkRangeService::class)->addressCount($cidr);
        } catch (NetworkRangeException $exception) {
            throw ValidationException::withMessages(['cidr' => $exception->getMessage()]);
        }
    }
}
