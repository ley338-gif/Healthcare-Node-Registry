<?php

return [
    // The operator must validate this period against its legal and contractual duties.
    'retention_days' => max(1, (int) env('AUDIT_RETENTION_DAYS', 7 * 365)),
    'prune_batch_size' => max(1, (int) env('AUDIT_PRUNE_BATCH_SIZE', 1000)),
];
