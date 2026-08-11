<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\DiscoveryRunChangedNotification;
use App\Notifications\RegistryDocumentExpiryNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class NotificationRedirectController extends Controller
{
    public function __invoke(Request $request, string $notification): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $storedNotification = $user->notifications()->findOrFail($notification);
        if ($storedNotification->type === RegistryDocumentExpiryNotification::class) {
            abort_unless($user->hasPermission('documents.view'), 403);
            $storedNotification->markAsRead();
            $documentPublicId = $storedNotification->data['document_public_id'] ?? null;

            return redirect()->route('documents.index', is_string($documentPublicId) ? ['document' => $documentPublicId] : []);
        }

        if ($storedNotification->type === DiscoveryRunChangedNotification::class) {
            abort_unless(
                $user->hasPermission('discovery.view')
                    || $user->hasPermission('discovery.run')
                    || $user->hasPermission('discovery.manage'),
                403,
            );
            $currentRunPublicId = $storedNotification->data['current_run_public_id'] ?? null;
            $baselineRunPublicId = $storedNotification->data['baseline_run_public_id'] ?? null;
            abort_unless(is_string($currentRunPublicId) && is_string($baselineRunPublicId), 404);
            $storedNotification->markAsRead();

            return redirect()->route('discovery.runs.compare', [
                'discoveryRun' => $currentRunPublicId,
                'baselineRun' => $baselineRunPublicId,
            ]);
        }

        abort(404);
    }
}
