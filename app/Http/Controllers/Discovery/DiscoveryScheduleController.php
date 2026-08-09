<?php

namespace App\Http\Controllers\Discovery;

use App\Http\Controllers\Controller;
use App\Models\DiscoveryRun;
use App\Models\DiscoverySchedule;
use App\Models\User;
use App\Services\Discovery\DiscoveryScheduleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class DiscoveryScheduleController extends Controller
{
    public function update(Request $request, DiscoverySchedule $discoverySchedule, DiscoveryScheduleService $service): RedirectResponse
    {
        Gate::authorize('create', DiscoveryRun::class);
        $validated = $request->validate(['active' => ['required', 'boolean']]);
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        $service->setActive($discoverySchedule, $validated['active'], $actor);

        return back()->with('success', $validated['active'] ? 'Discovery-Zeitplan wurde aktiviert.' : 'Discovery-Zeitplan wurde pausiert.');
    }

    public function destroy(Request $request, DiscoverySchedule $discoverySchedule, DiscoveryScheduleService $service): RedirectResponse
    {
        Gate::authorize('create', DiscoveryRun::class);
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);
        $service->delete($discoverySchedule, $actor);

        return back()->with('success', 'Discovery-Zeitplan wurde gelöscht. Bereits erzeugte Läufe bleiben erhalten.');
    }
}
