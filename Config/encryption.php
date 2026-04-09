<?php

return array (
  'ENABLED' => 'true                         # Enable/disable framework cryptography',
  'DRIVER' => 'openssl                       # Active crypto driver: openssl or sodium',
  'TYPE' => 'openssl                         # Backward-compatible alias for DRIVER',
  'KEY' => 'base64:S0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0s=  # Shared default 32-byte key (override in production)',
  'CIPHER' => 'AES-256-CBC                   # Default cipher alias used by framework consumers',
  'HASH_ALGORITHM' => 'sha256                # Default hashing / digest algorithm',
  'PBKDF2_ITERATIONS' => '100000             # Default PBKDF2 iteration count',
  'OPENSSL_CIPHER' => 'AES-256-CBC           # Preferred OpenSSL symmetric cipher',
  'OPENSSL' => 'AES-256-CBC                  # Backward-compatible alias for OPENSSL_CIPHER',
  'OPENSSL_KEY' => 'base64:S0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0tLS0s=  # Optional OpenSSL-specific 32-byte key',
  'SODIUM_KEY' => 'base64:U1NTU1NTU1NTU1NTU1NTU1NTU1NTU1NTU1NTU1NTU1M=    # Sodium SecretBox key',
  'SODIUM' => 'base64:U1NTU1NTU1NTU1NTU1NTU1NTU1NTU1NTU1NTU1NTU1M=        # Backward-compatible alias for SODIUM_KEY',
);
