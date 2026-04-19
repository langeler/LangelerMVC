<?php

declare(strict_types=1);

return [
    'DRIVER' => 'database',
    'NAME' => 'langelermvc_session',
    'LIFETIME' => '120',
    'EXPIRE_ON_CLOSE' => 'false',
    'ENCRYPT' => 'false',
    'SAVE' => [
        'PATH' => 'Storage/Sessions',
    ],
    'COOKIE' => [
        'PATH' => '/',
        'DOMAIN' => '',
        'SECURE' => 'true',
        'HTTPONLY' => 'true',
        'SAME_SITE' => 'Lax',
    ],
    'GC' => [
        'PROBABILITY' => '1',
        'DIVISOR' => '100',
        'MAX_LIFETIME' => '1440',
    ],
    'NATIVE' => [
        'HANDLER' => 'files',
        'STRICT_MODE' => 'true',
        'USE_COOKIES' => 'true',
        'USE_ONLY_COOKIES' => 'true',
        'SID_LENGTH' => '48',
    ],
    'DATABASE' => [
        'TABLE' => 'framework_sessions',
    ],
    'REDIS' => [
        'HOST' => '127.0.0.1',
        'PORT' => '6379',
        'TIMEOUT' => '0.0',
        'PASSWORD' => '',
        'DATABASE' => '0',
        'PREFIX' => 'langelermvc_session',
    ],
];
