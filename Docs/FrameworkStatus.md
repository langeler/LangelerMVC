# Framework Status

This document records the current implementation state of LangelerMVC based on the codebase and the latest verification pass as of `2026-04-11`.

## Snapshot

- PHP runtime used for the latest full verification pass: `8.4.12`
- Latest default regression result: `composer test`
- Verification result: `OK (81 tests, 1980 assertions)`
- Project posture: complete first-party platform framework with starter, identity, admin, catalog, cart, and order slices implemented
- Database verification posture: SQLite is exercised by the default suite; MySQL, PostgreSQL, and SQL Server have a dedicated matrix harness in `Tests/DbMatrix`

## Implemented And Working

### Runtime And Composition

- thin public front controller
- dedicated bootstrap layer for HTTP and console entrypoints
- reflection-driven container
- provider-based service registration and aliasing
- typed exception resolution
- module discovery and module route loading

### Configuration, Session, And Security Runtime

- tracked config files under `Config/`
- runtime `.env` override merging
- case-insensitive config lookups
- framework session facade
- file, database, and redis session drivers
- encrypted persisted session payload support through the framework crypto layer
- signed URL generation/verification
- cache-backed HTTP throttling support

### HTTP / MVC / Presentation

- request, response, controller, middleware, and service abstractions
- presenter abstraction with export helpers
- resource and resource-collection abstractions with meta/links/pagination support
- view abstraction with shared layout/page/partial/component rendering
- HTML + JSON negotiation through one controller pipeline

### Validation And Sanitization

- shared schema-driven sanitation/validation engine
- general filter-based implementations
- pattern-based implementations
- rule processing
- nested schema and collection-item schema support

### Database / SQL / Persistence

- lazy PDO-backed database runtime
- parameterized data query builder
- schema query builder
- model and repository foundations
- migration runner
- seed runner
- framework-managed migration history storage
- dependency-aware migration/seed ordering

### Infrastructure Subsystems

- cache manager and driver/provider resolution
- array, file, database, redis, and memcache cache backends
- crypto manager and OpenSSL/Sodium drivers
- event dispatcher
- queue manager with sync/database drivers
- failed-job store
- notification manager with mail/database channels
- payment manager with testing driver
- mail, OTP, and passkey/WebAuthn managers

### Utility Layer

- file manager
- iterator manager
- reflection manager
- finder subsystem
- normalized trait surface with collision coverage

### Console / Operational Tooling

- `console` entrypoint
- command kernel
- migration, seed, route, cache, config, and module commands
- queue work/retry/failed commands
- notification inspection command
- event/listener inspection command

## Implemented First-Party Modules

### `WebModule`

- request, controller, service, presenter, view, response
- `Page` model and repository
- route file
- `pages` migration and seed
- database-backed content by default
- shared HTML templates plus JSON/resource parity through the framework presentation pipeline

### `UserModule`

- session-backed authentication
- registration, login, logout
- password reset
- email verification
- roles, permissions, assignments, and RBAC checks
- TOTP-based 2FA with recovery codes
- passkey/WebAuthn registration and sign-in
- HTML + JSON endpoint parity

### `AdminModule`

- protected dashboard
- user and role/permission management
- module/config/cache/session visibility
- catalog/cart/order visibility
- queue/notification/event/payment operational visibility where safe

### `ShopModule`

- product and category persistence
- catalog listing/detail flows
- tracked public demo product imagery under `Public/assets/images`
- pricing and publish-state handling
- HTML + JSON parity through presenter/resource/view/response layers
- module migrations and seeds

### `CartModule`

- guest and authenticated carts
- session-backed cart identity
- persistent cart storage
- merge-on-login behavior through auth events
- item add/update/remove flows
- totals calculation in services
- HTML + JSON parity

### `OrderModule`

- checkout orchestration
- order, order-item, and order-address persistence
- cart snapshotting into orders
- order status and payment-state lifecycles
- payment manager integration through the testing driver
- order lifecycle notifications and listeners
- HTML + JSON parity

## What Is No Longer Missing

These framework/platform areas are now implemented rather than planned:

- starter/auth/admin/commerce application slices
- event dispatcher and listener registration
- queue subsystem and failed-job storage
- notification subsystem
- payment abstraction layer
- top-level config surfaces for notifications, queues, payments, and HTTP security
- passkey/WebAuthn and TOTP support behind framework-native boundaries

## Remaining Hardening / Environment Work

The framework is in a strong completed state, but a few items remain environment-dependent or are best treated as ongoing hardening rather than missing subsystems:

### 1. Live Database-Matrix Execution

The framework now has a dedicated DB-matrix harness, but live execution against MySQL, PostgreSQL, and SQL Server still depends on local or CI services being available and configured through environment variables.

### 2. Optional Runtime Backends

Redis, Memcache/Memcached, Imagick, and vendor-specific runtime backends are implemented behind framework boundaries, but real verification still depends on those PHP extensions and services being installed in the target environment.

### 3. Auth And Commerce Breadth

The major framework-level auth and commerce flows are implemented. The next gains are hardening and breadth:

- trusted-device / remember-device support for TOTP
- richer passkey device metadata and management UX
- broader real-world policy coverage as applications grow
- deeper end-to-end tests around queue-backed notifications and payment-state transitions in non-SQLite environments

## Recommended Ongoing Verification

For day-to-day framework development:

1. Run `composer test`
2. Run `composer test:db-matrix` when external databases are available
3. Use the console commands to verify operational flows such as migrations, seeds, routes, and queue handling

## Extension Outlook

LangelerMVC no longer needs missing-core work. The natural next layer is application growth and optional platform breadth, for example:

- richer admin diagnostics and audit surfaces
- additional notification channels
- additional payment drivers
- application-specific policies, events, and workflows
- optional developer generators on top of the now-stable console/runtime base
