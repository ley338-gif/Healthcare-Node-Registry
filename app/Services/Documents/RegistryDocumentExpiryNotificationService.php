<?php

namespace App\Services\Documents;

use App\Models\RegistryDocument;
use App\Models\User;
use App\Notifications\RegistryDocumentExpiryNotification;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

final class RegistryDocumentExpiryNotificationService
{
    /** @return array{documents: int, recipients: int, sent: int, skipped: int} */
    public function notify(): array
    {
        $warningEnd = CarbonImmutable::today()->addDays(max(0, (int) config('registry_documents.expiry_warning_days')));

        /** @var Collection<int, RegistryDocument> $documents */
        $documents = RegistryDocument::query()
            ->with('documentable')
            ->whereNull('archived_at')
            ->where('status', '!=', 'archived')
            ->whereNotNull('valid_until')
            ->whereDate('valid_until', '<=', $warningEnd)
            ->get();

        /** @var Collection<int, User> $recipients */
        $recipients = User::query()
            ->where('is_active', true)
            ->whereHas('roles.permissions', fn ($query) => $query->where('name', 'documents.view'))
            ->get();

        $sent = 0;
        $skipped = 0;
        foreach ($documents as $document) {
            foreach ($recipients as $recipient) {
                $alreadyNotified = $recipient->notifications()
                    ->where('type', RegistryDocumentExpiryNotification::class)
                    ->where('data->document_public_id', $document->public_id)
                    ->where('data->valid_until', $document->valid_until?->toDateString())
                    ->exists();

                if ($alreadyNotified) {
                    $skipped++;

                    continue;
                }

                $recipient->notify(new RegistryDocumentExpiryNotification($document));
                $sent++;
            }
        }

        return [
            'documents' => $documents->count(),
            'recipients' => $recipients->count(),
            'sent' => $sent,
            'skipped' => $skipped,
        ];
    }
}
