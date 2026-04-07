<?php

return array (
  'CONNECTION' => 'mysql             # mysql, pgsql, sqlite, sqlsrv',
  'HOST' => 'localhost',
  'PORT' => '3306',
  'DATABASE' => 'langelermvc',
  'USERNAME' => 'root',
  'PASSWORD' => 'root',
  'CHARSET' => 'utf8mb4              # Database charset (e.g., utf8, utf8mb4)',
  'COLLATION' => 'utf8mb4_unicode_ci # Database collation',
  'POOLING' => 'true                 # Enable connection pooling',
  'POOL' => '10                 # Max pool size',
  'FAILOVER' => '3307           # Secondary DB port',
  'TIMEOUT' => '30                   # Connection timeout in seconds',
  'RETRY' => '2000             # Delay between retries in milliseconds',
  'SSL' => 'prefer              # SSL mode: require, prefer, allow, disable',
  'REPLICATION' => 'false            # Enable replication for master-slave setups',
);
