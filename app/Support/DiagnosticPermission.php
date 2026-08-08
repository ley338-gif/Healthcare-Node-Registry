<?php

namespace App\Support;

use App\Models\User;

enum DiagnosticPermission: string
{
    case Echo = 'diagnostics.echo';
    case Worklist = 'diagnostics.worklist';
    case Query = 'diagnostics.query';
    case Store = 'diagnostics.store';
    case Move = 'diagnostics.move';
    case Get = 'diagnostics.get';
    case Mpps = 'diagnostics.mpps';
    case StorageCommitment = 'diagnostics.storage_commitment';
    case CapabilityMatrix = 'diagnostics.capability_matrix';

    public function label(): string
    {
        return match ($this) {
            self::Echo => 'DICOM C-ECHO und Netzwerkdiagnose ausführen',
            self::Worklist => 'DICOM Worklist-Abfrage ausführen',
            self::Query => 'DICOM C-FIND-Abfrage ausführen',
            self::Store => 'DICOM C-STORE-Test ausführen',
            self::Move => 'DICOM C-MOVE-Test ausführen',
            self::Get => 'DICOM C-GET-Test ausführen',
            self::Mpps => 'DICOM MPPS-Test ausführen',
            self::StorageCommitment => 'DICOM Storage Commitment testen',
            self::CapabilityMatrix => 'DICOM Capability-Matrix prüfen',
        };
    }

    public function allows(User $user): bool
    {
        return $user->hasPermission($this->value);
    }

    /** @return list<string> */
    public static function allowedDicomConnectionServices(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        $permissions = [
            'echo' => self::Echo,
            'store' => self::Store,
            'worklist' => self::Worklist,
            'query' => self::Query,
            'move' => self::Move,
            'get' => self::Get,
        ];

        return array_keys(array_filter(
            $permissions,
            static fn (self $permission): bool => $permission->allows($user),
        ));
    }

    public static function forProfileType(string $testType): ?self
    {
        return match ($testType) {
            'network', 'dicom_echo' => self::Echo,
            'worklist' => self::Worklist,
            'pacs_query' => self::Query,
            default => null,
        };
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        $labels = [];
        foreach (self::cases() as $permission) {
            $labels[$permission->value] = $permission->label();
        }

        return $labels;
    }
}
