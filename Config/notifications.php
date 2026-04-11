<?php

declare(strict_types=1);

return [
    'QUEUE' => false,
    'QUEUE_NAME' => 'notifications',
    'DEFAULT_CHANNELS' => ['database', 'mail'],
    'CHANNELS' => [
        'database' => [
            'ENABLED' => true,
        ],
        'mail' => [
            'ENABLED' => true,
        ],
    ],
];
