<?php

return [
    'move_test_study_instance_uid' => env(
        'DIAGNOSTIC_MOVE_TEST_STUDY_UID',
        '1.2.826.0.1.3680043.10.987.999.1',
    ),
    'get_test_study_instance_uid' => env(
        'DIAGNOSTIC_GET_TEST_STUDY_UID',
        '1.2.826.0.1.3680043.10.987.999.2',
    ),
    'network_timeout_seconds' => min(
        max((float) env('DIAGNOSTIC_NETWORK_TIMEOUT', 5), 1),
        10,
    ),
    'storage_commitment_callback_port' => min(max((int) env('DIAGNOSTIC_STORAGE_COMMITMENT_CALLBACK_PORT', 11113), 1), 65535),
    'storage_commitment_event_timeout_seconds' => min(max((int) env('DIAGNOSTIC_STORAGE_COMMITMENT_EVENT_TIMEOUT', 30), 5), 120),
];
