# Framework Status

This document records the current implementation state of LangelerMVC based on the codebase and verification status as of `2026-04-10`.

## Snapshot

- PHP runtime used for the latest verification pass: `8.4.12`
- Latest full regression result: `composer test`
- Verification result: `OK (74 tests, 1911 assertions)`
- Project posture: strong platform-level backend foundation, starter/content, identity, and admin slices implemented, broader commerce/event layers still to be built

## What Is Finished

### 1. Framework Runtime And Composition

Status: **implemented and working**

Implemented areas:

- thin public front controller
- dedicated bootstrap layer
- application runtime orchestration
- reflection-driven container
- provider-based service registration
- typed exception resolution
- module discovery and route loading

This means the framework has a real runtime boundary and is no longer just a folder skeleton.

### 2. Configuration And Session

Status: **implemented and working**

Implemented areas:

- tracked config files under `Config/`
- runtime environment override merging
- case-insensitive config lookups
- framework-managed session runtime
- default session storage under `Storage/Sessions`
- first-party file, database, and redis session driver adapters

This layer is in a good operational state for normal framework usage.

### 3. HTTP / MVC / Presentation Layer

Status: **implemented and working**

Implemented areas:

- request abstraction
- response abstraction
- controller / middleware / service base classes
- presenter abstraction
- resource abstraction and negotiated JSON response helpers
- resource collection abstraction with pagination/meta support
- view abstraction
- shared template rendering pipeline with default-layout-aware page rendering
- reusable partial/component template surface

The framework-level MVC surface is present and usable. The remaining limit is no longer the presentation API itself; it is the number of concrete business modules built on top of it.

### 4. Sanitation And Validation

Status: **implemented and working**

Implemented areas:

- shared schema-driven sanitizer/validator contract
- general filter-based implementations
- pattern-based implementations
- rule processing
- nested schema support
- collection item schema support

This subsystem is in a strong shape for new application work.

### 5. Database / SQL / Persistence Foundation

Status: **implemented and working**

Implemented areas:

- lazy database connection layer
- compiled SQL data query builder
- schema query builder
- model base class
- repository base class
- migration runner
- seed runner
- framework-managed migration history storage
- starter model/repository usage in `WebModule`

This is enough to build real domain persistence on top of the framework.

### 6. Cache Subsystem

Status: **implemented and working**

Implemented areas:

- cache contract and manager API
- driver/provider-based cache resolution
- shared payload lifecycle
- array, file, database, redis, and memcache cache backends
- namespace-aware clearing and pruning behavior
- runtime-gated unsupported driver handling

Important note:

- `Redis` and `Memcache` backends are implemented at framework level, but their real runtime use still depends on the corresponding PHP extensions and backend services being available.

### 7. Crypto Subsystem

Status: **implemented and working**

Implemented areas:

- crypto contract and manager API
- OpenSSL and Sodium driver support
- capability-aware driver handling
- key/cipher resolution through config
- safer error boundary and runtime checks

This subsystem is solid enough to support future secure storage, signed data, or protected application workflows.

### 8. Utility Layer

Status: **implemented and working**

Implemented areas:

- iterator manager
- reflection manager
- file manager
- finder system
- trait normalization and collision handling

These are now framework-grade services rather than thin wrappers over scattered native calls.

### 9. Console / Operational Tooling

Status: **implemented and working**

Implemented areas:

- first-party `console` entrypoint
- command kernel
- operational commands for migrations, seeds, routes, cache, config, and modules

This gives the framework a real maintenance/runtime surface outside HTTP.

### 10. Mail / OTP / Passkey Support Boundaries

Status: **implemented and working**

Implemented areas:

- framework-native mail manager
- framework-native OTP manager
- framework-native passkey/WebAuthn manager
- `Mailable` abstraction
- array/log/PHPMailer-backed transport handling through one manager boundary
- testing and WebAuthn-backed passkey drivers through one manager boundary

These are no longer just foundations. They are now exercised by the implemented identity layer.

### 11. WebModule Starter Slice

Status: **implemented starter slice**

Implemented areas:

- request
- controller
- service
- presenter
- view
- response
- route file
- model
- repository
- shared templates

Current limitation:

- `WebModule` uses memory-backed content by default, but it now also has framework-managed `pages` migration and seed classes ready for database-backed mode.

### 12. User Platform / Auth Layer

Status: **implemented and working**

Implemented areas:

- session-backed authentication
- registration, login, and logout
- password reset and email verification
- RBAC foundations with roles, permissions, and assignments
- TOTP-based 2FA with recovery-code support
- passkey/WebAuthn registration and authentication
- HTML + JSON auth surface parity

This is now the first real platform/business slice built on top of the framework foundation.

### 13. Admin Platform

Status: **implemented and working**

Implemented areas:

- admin dashboard surface
- user and role/permission management flows
- module/cache/config/session inspection flows
- permission-driven admin middleware for HTML and JSON routes

This gives the framework its first real protected management surface.

## What Is Partially Finished

### Application Modules

Status: **mixed**

Implemented modules:

- `WebModule`
- `UserModule`
- `AdminModule`

Scaffolded modules:

- `CartModule`
- `OrderModule`
- `ShopModule`

The folder architecture is present across all modules, but the commerce modules still do not contain real application behavior yet.

### Migration / Seed Structure

Status: **implemented foundation, minimal concrete usage present**

Available today:

- migration abstract base
- seed abstract base
- migration runner
- seed runner
- framework migration history table
- module-level `Migrations/` and `Seeds/` folders
- first concrete framework-managed `WebModule` migration and seed

### Templates

Status: **implemented and working**

Implemented today:

- `Layouts/WebShell.php`
- `Layouts/UserShell.php`
- `Layouts/AdminShell.php`
- `Pages/Home.php`
- `Pages/NotFound.php`
- `Pages/User*`
- `Pages/Admin*`
- reusable partials such as `PageIntro.php`, `StatusMessage.php`, and `PanelMeta.php`
- reusable components such as `BadgeList.php`, `DefinitionGrid.php`, `DataTable.php`, `LinkList.php`, and `CodeList.php`

## What Is Missing

These are the clearest not-yet-implemented framework/project areas:

- real commerce/business modules beyond `WebModule`, `UserModule`, and `AdminModule`
- notification subsystem
- event dispatcher / listener system
- queue subsystem

Important nuance:

- the framework now has a working identity/auth layer, but notifications, events, queues, and the commerce modules remain the major unfinished platform areas.

## Current Risks / Remaining Hardening Work

The framework is not broadly unstable, but a few areas are still best described as “next hardening work” rather than “done forever.”

### 1. Broader Infrastructure Test Matrix

The framework currently has strong PHPUnit coverage, but runtime execution is still concentrated around the local PHP environment and SQLite for database integration.

Recommended next verification additions:

- PostgreSQL integration tests
- MySQL integration tests
- Redis integration tests
- Memcache/Memcached integration tests
- session driver tests once those drivers exist

### 2. Application-Layer Growth

The framework is ahead of the app layer. That is fine, but it means the next real proof of framework quality comes from building additional modules that use the existing foundations heavily.

The strongest next domains are:

1. `ShopModule`
2. `CartModule`
3. `OrderModule`

### 3. Identity / Authorization Hardening

The main auth platform is implemented, but the next improvements are now hardening and breadth rather than first delivery.

Recommended next implementation areas:

- trusted-device / remember-device handling for 2FA
- recovery-code UX hardening and broader auth throttling
- richer passkey management flows and device metadata
- policy expansion as more business modules are added
- broader HTML/API auth regression coverage

### 4. Broader Platform Extensions

The project still lacks the next platform-tier services such as:

- event dispatcher and listeners
- notifications
- queue and failed-job handling
- rate limiting / signed URLs
- scaffolding and generator commands

That is a natural next framework enhancement once schema lifecycle work starts.

## Recommended Next Build Order

If the goal is a robust, scalable backend, the best next sequence is:

1. Implement `ShopModule` as the first commerce/catalog module.
2. Add `CartModule` with guest/authenticated cart persistence and merge-on-login behavior.
3. Implement `OrderModule` with checkout orchestration and order snapshots.
4. Add framework-native events and notifications on top of the current support managers.
5. Add queue/runtime extensions once events are stable.
6. Expand integration testing across real infrastructure backends.

## Potential Future Framework Extensions

These are not required to consider the current core successful, but they are natural extension tracks for LangelerMVC:

- mail and notification service layer
- API/JSON resource layer for non-HTML applications
- event/observer or job/queue system
- module generators and developer scaffolding commands
- richer admin tooling and diagnostics

Those should be built on the current core, not by replacing it.
