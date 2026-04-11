<?php

return array(
  'DRIVER' => 'native                  # native, file, database, redis',
  'NAME' => 'langelermvc_session       # Session name / cookie name',
  'LIFETIME' => '120                   # Session lifetime in minutes',
  'EXPIRE_ON_CLOSE' => 'false          # Expire the session cookie when the browser closes',
  'ENCRYPT' => 'false                  # Encrypt persisted session payloads using the configured crypto subsystem',
  'SAVE' =>
  array(
    'PATH' => 'Storage/Sessions        # Save path used by the native files handler',
  ),
  'COOKIE' =>
  array(
    'PATH' => '/                       # Cookie path',
    'DOMAIN' => '                      # Cookie domain',
    'SECURE' => 'true                  # Send session cookies over HTTPS only',
    'HTTPONLY' => 'true                # Prevent JavaScript access to the session cookie',
    'SAME_SITE' => 'Lax                # SameSite policy: Strict, Lax, or None',
  ),
  'GC' =>
  array(
    'PROBABILITY' => '1                # Garbage collection probability numerator',
    'DIVISOR' => '100                  # Garbage collection probability divisor',
    'MAX_LIFETIME' => '1440            # Garbage collection max lifetime in seconds',
  ),
  'NATIVE' =>
  array(
    'HANDLER' => 'files                # Supported native handler: files',
    'STRICT_MODE' => 'true             # Enable PHP strict session ID mode',
    'USE_COOKIES' => 'true             # Persist session IDs in cookies',
    'USE_ONLY_COOKIES' => 'true        # Prevent URL-based session IDs',
    'SID_LENGTH' => '48                # Session ID length',
  ),
  'DATABASE' =>
  array(
    'TABLE' => 'framework_sessions     # Database-backed session table',
  ),
  'REDIS' =>
  array(
    'HOST' => '127.0.0.1',
    'PORT' => '6379',
    'TIMEOUT' => '0.0',
    'PASSWORD' => '',
    'DATABASE' => '0',
    'PREFIX' => 'langelermvc_session   # Redis session key prefix',
  ),
);
