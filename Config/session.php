<?php

return array (
  'DRIVER' => 'native           # file, cookie, database, redis, memcached, native',
  'LIFETIME' => '120            # Session lifetime in minutes',
  'SECURE' => 'true      # Secure cookies (HTTPS only)',
  'HTTPONLY' => 'true    # HTTP-only cookies (no JS access)',
  'SAME' => 'lax           # Same-site cookie policy: strict, lax, none',
  'ENCRYPT' => 'false           # Enable/disable session encryption (use global encryption settings)',
  'NATIVE' => 'files    # Native handler: files, redis, memcached',
  'SAVE' => 'Storage/Sessions  # Session save path',
  'GC' => '1440     # Max lifetime for garbage collection (in seconds)',
);
