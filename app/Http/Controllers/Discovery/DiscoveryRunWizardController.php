<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\Controller;
use App\Models\DicomNode;
use App\Models\DiscoveryAllowedNetwork;
use App\Models\DiscoveryPortProfile;
use App\Models\DiscoveryRun;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class DiscoveryRunWizardController extends Controller
{
    public function create(Request $request): Response
    {
        Gate::authorize('create', DiscoveryRun::class);

        $portProfiles = DiscoveryPortProfile::query()
            ->active()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->map(fn (DiscoveryPortProfile $profile): array => [
                'public_id' => $profile->public_id,
                'name' => $profile->name,
                'description' => $profile->description,
                'ports' => $profile->ports,
                'is_default' => $profile->is_default,
            ])->values();

        $registryAeTitles = DicomNode::query()->active()->distinct()->orderBy('ae_title')->pluck('ae_title')->values();

        return Inertia::render('Discovery/Wizard/Create', [
            'portProfiles' => $portProfiles,
            'registryAeTitles' => $registryAeTitles,
            'defaultCallingAe' => config('discovery.default_calling_ae_title', 'HNR_DISCOVERY'),
            'maxRangeSize' => (int) config('discovery.max_range_size', 1024),
            'largeRangeWarningThreshold' => (int) config('discovery.large_range_warning_threshold', 256),
            'maxParallelHostsLimit' => (int) config('discovery.max_parallel_hosts', 16),
            'maxAeAttemptsPerPort' => (int) config('discovery.max_ae_attempts_per_port', 5),
            'defaultScheduleTimezone' => (string) config('app.timezone', 'UTC'),
            'allowedNetworks' => DiscoveryAllowedNetwork::query()->active()->orderBy('cidr')->get(['cidr', 'description']),
        ]);
    }
}
