<?php

use App\Support\DiagnosticPermission;

return [
    'challenge_ttl_seconds' => 300,
    'max_attempts' => 5,
    'required_permissions' => [
        'users.manage',
        'roles.manage',
        'settings.manage',
        'registry.manage',
        'documents.upload',
        'documents.update',
        'documents.archive',
        'documents.manage_versions',
        'documents.approve',
        'discovery.run',
        'discovery.manage',
        'tests.run.storage',
        ...array_map(static fn (DiagnosticPermission $permission): string => $permission->value, DiagnosticPermission::cases()),
    ],
];
