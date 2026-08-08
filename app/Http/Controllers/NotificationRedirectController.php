<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\RegistryDocumentExpiryNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class NotificationRedirectController extends Controller
{
    public function __invoke(Request $request, string $notification): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->hasPermission('documents.view'), 403);

        $storedNotification = $user->notifications()->findOrFail($notification);
        abort_unless($storedNotification->type === RegistryDocumentExpiryNotification::class, 404);
        $storedNotification->markAsRead();
        $documentPublicId = $storedNotification->data['document_public_id'] ?? null;

        return redirect()->route('documents.index', is_string($documentPublicId) ? ['document' => $documentPublicId] : []);
    }
}
