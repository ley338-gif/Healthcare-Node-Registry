<?php

return [
    'move_test_study_instance_uid' => env(
        'DIAGNOSTIC_MOVE_TEST_STUDY_UID',
        '1.2.826.0.1.3680043.10.987.999.1',
    ),
    'network_timeout_seconds' => min(
        max((float) env('DIAGNOSTIC_NETWORK_TIMEOUT', 5), 1),
        10,
    ),
];
