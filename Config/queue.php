<?php

declare(strict_types=1);

return [
    'DRIVER' => 'sync',
    'DEFAULT_QUEUE' => 'default',
    'RETRY_AFTER' => 60,
    'DRIVERS' => [
        'sync' => [
            'ENABLED' => true,
        ],
        'database' => [
            'ENABLED' => true,
        ],
    ],
];
