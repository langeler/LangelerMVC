<?php

declare(strict_types=1);

return [
    'DRIVER' => 'sync',
    'DEFAULT_QUEUE' => 'default',
    'RETRY_AFTER' => 60,
    'MAX_ATTEMPTS' => 3,
    'BACKOFF' => [
        'STRATEGY' => 'exponential',
        'SECONDS' => 5,
        'MAX_SECONDS' => 300,
    ],
    'WORKER' => [
        'SLEEP' => 1,
        'MAX_RUNTIME' => 0,
        'MAX_MEMORY_MB' => 256,
        'CONTROL_PATH' => 'Storage/Framework/Queue',
    ],
    'FAILED' => [
        'PRUNE_AFTER_HOURS' => 168,
    ],
    'DRIVERS' => [
        'sync' => [
            'ENABLED' => true,
        ],
        'database' => [
            'ENABLED' => true,
        ],
    ],
];
