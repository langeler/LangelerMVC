<?php

declare(strict_types=1);

return [
    'ENABLED' => 'true',
    'DRIVER' => 'file',
    'PREFIX' => 'langelermvc_cache',
    'TTL' => '3600',
    'COMPRESSION' => 'true',
    'SERIALIZATION' => 'php',
    'ENCRYPT' => 'false',
    'MAX_ITEMS' => '0',
    'FILE' => 'Storage/Cache',
    'TABLE' => 'cache',
    'REDIS_HOST' => '127.0.0.1',
    'REDIS_PORT' => '6379',
    'REDIS_TIMEOUT' => '1.5',
    'REDIS_DATABASE' => '0',
    'REDIS_PASSWORD' => '',
    'MEMCACHE_HOST' => '127.0.0.1',
    'MEMCACHE_PORT' => '11211',
    'MEMCACHE_WEIGHT' => '0',
];
