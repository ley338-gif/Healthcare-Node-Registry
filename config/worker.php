<?php

return [
    'heartbeat_path' => storage_path('framework/worker-heartbeat'),
    'heartbeat_max_age_seconds' => 15,
    'default_job_timeout_seconds' => 60,
    'job_timeout_grace_seconds' => 15,
];
