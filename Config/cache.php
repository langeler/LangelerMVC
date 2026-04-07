<?php

return array (
  'ENABLED' => 'true         # Enable/disable cache functionality',
  'DRIVER' => 'file              # Cache driver: file, redis, memcached, database, array',
  'PREFIX' => 'langelermvc_cache  # Cache prefix to avoid conflicts',
  'TTL' => '3600                  # Default cache TTL (time-to-live) in seconds',
  'COMPRESSION' => 'true          # Enable compression for cached data',
  'SERIALIZATION' => 'php         # Cache serialization method: php, json, igbinary',
  'ENCRYPT' => 'false             # Enable/disable cache encryption (use global encryption settings)',
  'REDIS' => '0          # Redis database index for cache',
  'MEMCACHED' => '100      # Memcached server weight',
  'FILE' => '/var/www/html/cache  # Path for file-based cache storage',
);
