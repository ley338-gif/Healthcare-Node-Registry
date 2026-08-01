<?php

namespace App\Support;

use App\Models\SecurityEvent;
use Illuminate\Database\Eloquent\Builder;

enum AuditEventGroup: string
{
    case Registry = 'registry';
    case Dicom = 'dicom';
    case Documentation = 'documentation';
    case Documents = 'documents';
    case Tests = 'tests';
    case Users = 'users';
    case Administration = 'administration';
    case Security = 'security';

    public function label(): string
    {
        return match ($this) {
            self::Registry => 'Registry',
            self::Dicom => 'DICOM',
            self::Documentation => 'Dokumentation',
            self::Documents => 'Dokumente',
            self::Tests => 'Tests',
            self::Users => 'Benutzer',
            self::Administration => 'Administration',
            self::Security => 'Sicherheit',
        };
    }

    public static function fromEventType(string $eventType): self
    {
        return match (true) {
            str_starts_with($eventType, 'registry.documentation.') => self::Documentation,
            str_starts_with($eventType, 'document.') => self::Documents,
            str_starts_with($eventType, 'diagnostics.'), str_contains($eventType, '.test') => self::Tests,
            str_starts_with($eventType, 'registry.dicom_') => self::Dicom,
            str_starts_with($eventType, 'user.'), str_starts_with($eventType, 'account.') => self::Users,
            str_starts_with($eventType, 'admin.'), str_starts_with($eventType, 'settings.'),
            str_starts_with($eventType, 'role.'), str_starts_with($eventType, 'permission.') => self::Administration,
            str_starts_with($eventType, 'auth.'), str_starts_with($eventType, 'security.'),
            in_array($eventType, ['login', 'logout'], true) => self::Security,
            default => self::Registry,
        };
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(static fn (self $group): array => [
            'value' => $group->value,
            'label' => $group->label(),
        ], self::cases());
    }

    /** @param Builder<SecurityEvent> $query */
    public function apply(Builder $query): void
    {
        $query->where(function (Builder $group): void {
            match ($this) {
                self::Documentation => $group->where('event_type', 'like', 'registry.documentation.%'),
                self::Documents => $group->where('event_type', 'like', 'document.%'),
                self::Tests => $group->where('event_type', 'like', 'diagnostics.%')->orWhere('event_type', 'like', '%.test%'),
                self::Dicom => $group->where('event_type', 'like', 'registry.dicom\_%'),
                self::Users => $group->where('event_type', 'like', 'user.%')->orWhere('event_type', 'like', 'account.%'),
                self::Administration => $group->where('event_type', 'like', 'admin.%')->orWhere('event_type', 'like', 'settings.%')->orWhere('event_type', 'like', 'role.%')->orWhere('event_type', 'like', 'permission.%'),
                self::Security => $group->where('event_type', 'like', 'auth.%')->orWhere('event_type', 'like', 'security.%')->orWhereIn('event_type', ['login', 'logout']),
                self::Registry => $this->applyRegistryFallback($group),
            };
        });
    }

    /** @param Builder<SecurityEvent> $query */
    private function applyRegistryFallback(Builder $query): void
    {
        $query->where('event_type', 'not like', 'registry.documentation.%')
            ->where('event_type', 'not like', 'document.%')
            ->where('event_type', 'not like', 'diagnostics.%')
            ->where('event_type', 'not like', '%.test%')
            ->where('event_type', 'not like', 'registry.dicom\_%')
            ->where('event_type', 'not like', 'user.%')->where('event_type', 'not like', 'account.%')
            ->where('event_type', 'not like', 'admin.%')->where('event_type', 'not like', 'settings.%')
            ->where('event_type', 'not like', 'role.%')->where('event_type', 'not like', 'permission.%')
            ->where('event_type', 'not like', 'auth.%')->where('event_type', 'not like', 'security.%')
            ->whereNotIn('event_type', ['login', 'logout']);
    }
}
