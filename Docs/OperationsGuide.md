# Operations Guide

This document covers the framework-native operational surfaces that are now part of LangelerMVC itself rather than being left to application-local conventions.

## Health Endpoints

LangelerMVC now exposes built-in health endpoints through `App\Core\App`:

- `GET /health`: liveness
- `GET /ready`: readiness

These routes are handled before normal router dispatch when the `health` core service is available.

### What They Report

- `live`: runtime availability, PHP version, SAPI, and application identity
- `ready`: database, cache, session, queue, notification, payment, mail, passkey, and audit checks
- `capabilities`: available drivers, enabled features, registered modules, and event/listener visibility

## Console Operations

The current first-party operational commands include:

```bash
php console list
php console health:check
php console health:check ready
php console audit:list --limit=25
php console migrate
php console seed
php console route:list
php console queue:work notifications
php console queue:failed
php console queue:retry
php console event:list
php console notification:list
```

Composer shortcuts are also available:

```bash
composer ops:health
composer ops:ready
composer ops:audit
composer verify:platform
```

## Audit Logging

The framework now ships with a built-in audit logger backed by the `framework_audit_log` table.

Current first-party audit events include:

- authentication sign-in/sign-out and failed sign-in
- OTP enable/disable, recovery regeneration, and trusted-device actions
- passkey registration, authentication, and deletion
- role and permission synchronization from the admin surface
- order creation and payment-state transitions

Audit logging is configured through `Config/operations.php`.

## Trusted Devices

TOTP now supports trusted-device / remember-device behavior through the framework identity layer.

- trusted-device tokens are persisted through `UserAuthTokenRepository`
- the browser token is stored in the configured OTP trusted-device cookie
- trusted devices can be revoked from the user profile flow
- profile payloads now expose trusted-device visibility for both HTML and JSON responses

The main settings live in `Config/auth.php`:

- `OTP.TRUSTED_DEVICE_DAYS`
- `OTP.TRUSTED_DEVICE_COOKIE`

## Local Verification Stack

LangelerMVC now includes a local backend verification stack in `docker-compose.verify.yml`.

Services provided:

- MySQL
- PostgreSQL
- SQL Server
- Redis
- Memcached

Typical usage:

```bash
docker compose -f docker-compose.verify.yml up -d
composer test
composer test:db-matrix
composer test:mysql
composer test:pgsql
composer test:sqlsrv
composer ops:health
```

Redis, Memcached, and Imagick verification still depend on the relevant PHP extensions being available in the environment where the framework tests are executed.

## CI Posture

GitHub Actions now provides:

- default regression coverage through `.github/workflows/php.yml`
- supported DB-matrix coverage for MySQL and PostgreSQL in CI

SQL Server verification remains part of the local/containerized workflow because hosted runner support can vary more across environments than the framework code itself.
