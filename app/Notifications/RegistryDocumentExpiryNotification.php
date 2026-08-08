<?php

namespace App\Notifications;

use App\Models\RegistryDocument;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class RegistryDocumentExpiryNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly RegistryDocument $document) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, int|string|null> */
    public function toDatabase(object $notifiable): array
    {
        $this->document->loadMissing('documentable');
        $validUntil = $this->document->valid_until;
        $daysRemaining = $validUntil === null
            ? null
            : (int) CarbonImmutable::today()->diffInDays($validUntil, false);

        return [
            'kind' => 'registry_document_expiry',
            'document_public_id' => $this->document->public_id,
            'title' => $this->document->title,
            'category' => $this->document->category->value,
            'category_label' => $this->document->category->label(),
            'context_name' => $this->document->documentable?->getAttribute('name'),
            'valid_until' => $validUntil?->toDateString(),
            'days_remaining' => $daysRemaining,
            'status' => $daysRemaining !== null && $daysRemaining < 0 ? 'expired' : 'expiring_soon',
            'url' => '/documents?'.http_build_query(['document' => $this->document->public_id]),
        ];
    }
}
