<?php

declare(strict_types=1);

return [
    'CURRENCY' => 'SEK',
    'TAX' => [
        'RATE_BPS' => 2500,
    ],
    'SHIPPING' => [
        'FLAT_RATE_MINOR' => 1490,
        'FREE_OVER_MINOR' => 50000,
    ],
    'DISCOUNT' => [
        'RATE_BPS' => 0,
        'MAX_MINOR' => 0,
    ],
    'INVENTORY' => [
        'RESERVE_ON_CHECKOUT' => true,
        'RELEASE_ON_CANCEL' => true,
    ],
    'FULFILLMENT' => [
        'AUTO_READY_ON_CAPTURE' => true,
    ],
];
