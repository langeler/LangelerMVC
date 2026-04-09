<?php

return array(
  'ENABLED' => 'true                    # Enable or disable framework caching',
  'DRIVER' => 'file                     # Supported drivers: array, file, database, redis, memcache',
  'PREFIX' => 'langelermvc_cache        # Cache namespace used across all drivers',
  'TTL' => '3600                        # Default cache TTL in seconds; 0 stores entries indefinitely',
  'COMPRESSION' => 'true                # Compress cache payloads before optional encryption',
  'SERIALIZATION' => 'php               # Supported serializers: php, json, igbinary',
  'ENCRYPT' => 'false                   # Encrypt cache payloads using the configured crypto subsystem',
  'MAX_ITEMS' => '0                     # Optional per-prefix cache limit; 0 disables pruning',
  'FILE' => 'Storage/Cache              # Directory used by the file cache driver',
  'TABLE' => 'cache                     # Database table used by the database cache driver',
  'REDIS_HOST' => '127.0.0.1            # Redis host for the redis cache driver',
  'REDIS_PORT' => '6379                 # Redis port for the redis cache driver',
  'REDIS_TIMEOUT' => '1.5               # Redis connection timeout in seconds',
  'REDIS_DATABASE' => '0                # Redis database index for cache entries',
  'REDIS_PASSWORD' => '                 # Optional Redis password',
  'MEMCACHE_HOST' => '127.0.0.1         # Memcached host for the memcache cache driver',
  'MEMCACHE_PORT' => '11211             # Memcached port for the memcache cache driver',
  'MEMCACHE_WEIGHT' => '0               # Optional Memcached server weight',
);
