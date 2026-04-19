<?php

declare(strict_types=1);

return [
    'DRIVER' => 'testing',
    'CURRENCY' => 'SEK',
    'DEFAULT_METHOD' => 'card',
    'DEFAULT_FLOW' => 'authorize_capture',
    'DRIVERS' => [
        'testing' => [
            'ENABLED' => true,
            'AUTHORIZE_IMMEDIATELY' => true,
            'METHODS' => [
                'card',
                'wallet',
                'bank_transfer',
                'bnpl',
                'local_instant',
                'manual',
                'crypto',
            ],
            'FLOWS' => [
                'authorize_capture',
                'purchase',
                'redirect',
                'async',
                'manual_review',
            ],
        ],
    ],
];
