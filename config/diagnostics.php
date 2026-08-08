<?php

return [
    'default_calling_ae_title' => env('DIAGNOSTIC_CALLING_AE_TITLE', 'NODE_REGISTRY'),
    'python_binary' => env('DIAGNOSTIC_PYTHON_BINARY', '/opt/registry-dicom/bin/python'),
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
    'tls' => [
        'profile_directory' => env('DIAGNOSTIC_TLS_PROFILE_DIRECTORY', '/run/secrets/dicom-tls'),
        'private_key_filename' => 'private-key.pem',
        'certificate_filename' => 'certificate.pem',
        'ca_certificate_filename' => 'ca.pem',
    ],
];
