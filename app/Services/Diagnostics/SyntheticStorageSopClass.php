<?php

namespace App\Services\Diagnostics;

enum SyntheticStorageSopClass: string
{
    case SecondaryCapture = 'secondary_capture';
    case CtImage = 'ct_image';
    case MrImage = 'mr_image';

    public function uid(): string
    {
        return match ($this) {
            self::SecondaryCapture => '1.2.840.10008.5.1.4.1.1.7',
            self::CtImage => '1.2.840.10008.5.1.4.1.1.2',
            self::MrImage => '1.2.840.10008.5.1.4.1.1.4',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::SecondaryCapture => 'Secondary Capture Image Storage',
            self::CtImage => 'CT Image Storage',
            self::MrImage => 'MR Image Storage',
        };
    }
}
