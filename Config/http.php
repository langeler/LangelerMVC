<?php

declare(strict_types=1);

return [
    'SIGNED_URL' => [
        'KEY' => 'langelermvc-signed-url',
    ],
    'THROTTLE' => [
        'MAX_ATTEMPTS' => 5,
        'DECAY_SECONDS' => 60,
    ],
];
