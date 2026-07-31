<?php

return [
    'network_timeout_seconds' => min(
        max((float) env('DIAGNOSTIC_NETWORK_TIMEOUT', 5), 1),
        10,
    ),
];
