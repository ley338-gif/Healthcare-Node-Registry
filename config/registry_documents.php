<?php

return [
    'disk' => env('REGISTRY_DOCUMENT_DISK', 'registry_documents'),
    'max_upload_kb' => (int) env('REGISTRY_DOCUMENT_MAX_UPLOAD_KB', 25 * 1024),
    'expiry_warning_days' => (int) env('REGISTRY_DOCUMENT_EXPIRY_WARNING_DAYS', 60),
    'malware_scanner' => [
        'enabled' => (bool) env('REGISTRY_DOCUMENT_MALWARE_SCANNER_ENABLED', false),
        'host' => (string) env('REGISTRY_DOCUMENT_MALWARE_SCANNER_HOST', 'clamav'),
        'port' => (int) env('REGISTRY_DOCUMENT_MALWARE_SCANNER_PORT', 3310),
        'connect_timeout_seconds' => (float) env('REGISTRY_DOCUMENT_MALWARE_SCANNER_CONNECT_TIMEOUT', 2),
        'read_timeout_seconds' => (int) env('REGISTRY_DOCUMENT_MALWARE_SCANNER_READ_TIMEOUT', 30),
        'chunk_size_bytes' => 8192,
    ],
];
