<?php

return [
    'disk' => env('REGISTRY_DOCUMENT_DISK', 'registry_documents'),
    'max_upload_kb' => (int) env('REGISTRY_DOCUMENT_MAX_UPLOAD_KB', 25 * 1024),
];
