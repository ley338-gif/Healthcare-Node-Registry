<?php

namespace App\Support;

enum RegistryDocumentValidityStatus: string
{
    case Active = 'active';
    case ExpiringSoon = 'expiring_soon';
    case Expired = 'expired';
    case Undated = 'undated';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktiv',
            self::ExpiringSoon => 'Läuft bald ab',
            self::Expired => 'Abgelaufen',
            self::Undated => 'Ohne Gültigkeitsdatum',
            self::Archived => 'Archiviert',
        };
    }
}
