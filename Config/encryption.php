<?php

return array (
  'ENABLED' => 'true         # Enable/disable encryption globally',
  'TYPE' => 'openssl         # Encryption type: openssl, sodium',
  'KEY' => 'base64:YourGlobalEncryptionKeyHere  # Global encryption key',
  'CIPHER' => 'AES-256-CBC   # Cipher used for encryption (e.g., AES-128-CBC, AES-256-CBC)',
  'HASH' => '12       # Number of rounds for hashing algorithms (bcrypt, argon2)',
  'OPENSSL' => 'AES-256-CBC      # Cipher for OpenSSL encryption',
  'SODIUM' => 'base64:YourSodiumKeyHere  # Sodium-specific encryption key',
);
