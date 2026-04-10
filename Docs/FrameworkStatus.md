# Framework Status

This document records the current implementation state of LangelerMVC based on the codebase and verification status as of `2026-04-10`.

## Snapshot

- PHP runtime used for the latest verification pass: `8.4.12`
- Latest full regression result: `composer test`
- Verification result: `OK (64 tests, 1839 assertions)`
- Project posture: strong platform-level backend foundation, starter application slice implemented, broader domain layer still to be built

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
- view abstraction
- shared template rendering pipeline

The framework-level MVC surface is present and usable. What is still limited is the number of concrete application modules using it.

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

### 10. Mail / OTP Support Boundaries

Status: **implemented and working**

Implemented areas:

- framework-native mail manager
- framework-native OTP manager
- `Mailable` abstraction
- array/log/PHPMailer-backed transport handling through one manager boundary

These are foundation services; the business/auth flows that should use them still need to be implemented.

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

## What Is Partially Finished

### Application Modules

Status: **scaffolded, not implemented**

Modules with structure only:

- `AdminModule`
- `CartModule`
- `OrderModule`
- `ShopModule`
- `UserModule`

The folder architecture is present, but these modules do not yet contain real application behavior.

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

Status: **starter surface implemented**

Implemented today:

- `Layouts/WebShell.php`
- `Pages/Home.php`
- `Pages/NotFound.php`

Scaffolded only:

- `Components/`
- `Partials/`

## What Is Missing

These are the clearest not-yet-implemented framework/project areas:

- real business modules beyond `WebModule`
- authentication / authorization workflow layer
- RBAC / policy / gate system
- password reset / email verification flows
- notification subsystem
- event dispatcher / listener system
- queue subsystem

Important nuance:

- the framework now has mail/OTP foundations, but no module-level identity workflow is using them yet.

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

1. `UserModule`
2. `AdminModule`
3. `ShopModule`
4. `CartModule`
5. `OrderModule`

### 3. Identity / Authorization Layer

The main gap has now shifted from platform tooling to identity and policy workflow.

Recommended next implementation areas:

- session authentication manager
- user provider / password broker
- email verification flow
- OTP challenge flow
- RBAC / role-permission-policy resolution

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

1. Build the migration/seed runner.
2. Add the first real `pages` migration and seed for `WebModule`.
3. Switch `WebModule` from memory-backed to database-backed content.
4. Implement `UserModule` as the first real domain module.
5. Add CLI tooling around migrations, seeds, cache, and routes.
6. Expand integration testing across real infrastructure backends.

## Potential Future Framework Extensions

These are not required to consider the current core successful, but they are natural extension tracks for LangelerMVC:

- framework-native authentication and authorization package
- mail and notification service layer
- API/JSON resource layer for non-HTML applications
- event/observer or job/queue system
- module generators and developer scaffolding commands
- richer admin tooling and diagnostics

Those should be built on the current core, not by replacing it.
