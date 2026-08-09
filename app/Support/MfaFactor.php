<?php

namespace App\Support;

enum MfaFactor: string
{
    case Totp = 'totp';
    case RecoveryCode = 'recovery_code';
}
