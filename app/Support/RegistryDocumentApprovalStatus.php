<?php

namespace App\Support;

enum RegistryDocumentApprovalStatus: string
{
    case Draft = 'draft';
    case PendingReview = 'pending_review';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Entwurf',
            self::PendingReview => 'Wartet auf Freigabe',
            self::Approved => 'Freigegeben',
            self::Rejected => 'Abgelehnt',
        };
    }
}
