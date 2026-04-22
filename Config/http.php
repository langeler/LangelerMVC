<?php

declare(strict_types=1);

return [
    'CSRF' => [
        'ENABLED' => true,
        'FIELD' => '_token',
        'HEADER' => 'X-CSRF-TOKEN',
    ],
    'HEADERS' => [
        'CONTENT_SECURITY_POLICY' => "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'; object-src 'none'; img-src 'self' data: https:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; connect-src 'self'; font-src 'self' data:",
        'PERMISSIONS_POLICY' => 'camera=(), geolocation=(), microphone=(), payment=()',
        'REFERRER_POLICY' => 'strict-origin-when-cross-origin',
        'X_CONTENT_TYPE_OPTIONS' => 'nosniff',
        'X_FRAME_OPTIONS' => 'SAMEORIGIN',
        'CROSS_ORIGIN_OPENER_POLICY' => 'same-origin',
        'CROSS_ORIGIN_RESOURCE_POLICY' => 'same-origin',
        'STRICT_TRANSPORT_SECURITY' => 'max-age=31536000; includeSubDomains',
    ],
    'SIGNED_URL' => [
        'KEY' => 'langelermvc-signed-url',
    ],
    'THROTTLE' => [
        'MAX_ATTEMPTS' => 5,
        'DECAY_SECONDS' => 60,
    ],
];
