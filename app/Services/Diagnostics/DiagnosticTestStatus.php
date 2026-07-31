<?php

namespace App\Services\Diagnostics;

enum DiagnosticTestStatus: string
{
    case Success = 'success';
    case Warning = 'warning';
    case Failed = 'failed';
    case Timeout = 'timeout';
    case Cancelled = 'cancelled';
    case Unsupported = 'unsupported';
}
