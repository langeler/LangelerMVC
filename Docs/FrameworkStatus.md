# Framework Status

This document records the current implementation state of LangelerMVC based on the codebase and the latest verification pass as of `2026-04-30`.

## Snapshot

- PHP runtime used for the latest full verification pass: `8.4.12`
- Latest default regression result: `composer test`
- Verification result: `OK (135 tests, 3041 assertions)`
- Project posture: complete first-party platform framework with starter, identity, admin operations, WebModule content authoring, catalog, cart, promotions, subscriptions, inventory reservations, returns/exchanges, VAT/order documents, and order slices implemented
- Database verification posture: SQLite is exercised by the default suite; MySQL, PostgreSQL, and SQL Server have a dedicated matrix harness in `Tests/DbMatrix`

## Implemented And Working

### Runtime And Composition

- thin public front controller
- dedicated bootstrap layer for HTTP and console entrypoints
- reflection-driven container
- provider-based service registration and aliasing
- framework-native liveness/readiness/capability reporting
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
- framework-native `.vide` template engine with `.lmv` and `.php` compatibility fallbacks
- first-party `.vide` template sources authored without raw PHP tags and enforced by regression coverage
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
- seed dependency resolution across repositories and framework services

### Infrastructure Subsystems

- cache manager and driver/provider resolution
- array, file, database, redis, and memcache cache backends
- crypto manager and OpenSSL/Sodium drivers
- event dispatcher
- queue manager with sync/database drivers
- failed-job store
- notification manager with mail/database channels
- payment manager with a plug-and-play multi-driver compatibility surface
- first-party payment drivers for card, crypto, PayPal, Klarna, Swish, Qliro, Walley, and the framework testing/reference driver
- signed/idempotent payment webhook ingestion with event ledgers and order lifecycle reconciliation
- signed/idempotent subscription webhook ingestion with recurring renewal, dunning, cancellation, pause/resume, and renewal-order reconciliation
- inventory reservation ledger support with reserve, commit, release, expiry, order attachment, and admin visibility
- return, exchange, partial-refund, VAT invoice, credit-note, packing-slip, and return-authorization workflows
- mail, OTP, passkey/WebAuthn, health, and audit managers

### Utility Layer

- file manager
- iterator manager
- reflection manager
- finder subsystem
- normalized trait surface with collision coverage
- framework-consistent JSON/payload serialization across async, auth, console, and commerce flows
- shared money formatting and auth helper fallbacks reused across module services and repositories

### Console / Operational Tooling

- `console` entrypoint
- command kernel
- migration, seed, route, cache, config, and module commands
- health and audit inspection commands
- queue work/retry/failed commands
- notification inspection command
- event/listener inspection command
- release readiness inspection command through `php console release:check`
- GitHub Actions workflow with platform checks, explicit MySQL/PostgreSQL readiness waits, target diagnostics, and DB service log artifacts on failure

## Implemented First-Party Modules

### `WebModule`

- request, controller, service, presenter, view, response
- `Page` model and repository
- route file
- `pages` migration and seed
- database-backed content by default
- admin-native page authoring, publishing, unpublishing, deletion, and home-page deletion guardrails
- shared HTML templates plus JSON/resource parity through the framework presentation pipeline

### `UserModule`

- session-backed authentication
- registration, login, logout
- password reset
- email verification
- roles, permissions, assignments, and RBAC checks
- TOTP-based 2FA with recovery codes and trusted-device support
- passkey/WebAuthn registration and sign-in
- HTML + JSON endpoint parity

### `AdminModule`

- protected dashboard
- user and role/permission management
- WebModule page authoring and publishing
- module/config/cache/session visibility
- catalog/cart/order visibility
- database-backed promotion/coupon management with usage reporting, analytics, and bulk lifecycle workflows
- per-customer and per-segment promotion usage enforcement through checkout usage ledgers
- structured queue/notification/event/payment/health/inventory/return/document operational visibility where safe
- framework health/readiness/capability visibility
- audit-aware operational visibility and drilldowns where safe
- intentional orchestration-only posture for persistence: admin reuses the runtime and domain repositories it manages instead of introducing separate admin-owned tables/models

### Project Packaging And Verification

- composer scripts for regression, DB-matrix, health inspection, audit inspection, and platform verification
- local backend verification stack through `docker-compose.verify.yml`
- GitHub Actions workflow for default regression plus supported DB-matrix execution
- first-run installer wizard through `Public/install/index.php` with bootstrap redirect handling and guided environment/database/admin provisioning

### `ShopModule`

- product and category persistence
- catalog listing/detail flows
- tracked public demo product imagery under `Public/assets/images`
- pricing and publish-state handling
- catalog lifecycle notifications for admin-driven product/category saves
- HTML + JSON parity through presenter/resource/view/response layers
- module migrations and seeds

### `CartModule`

- guest and authenticated carts
- session-backed cart identity
- persistent cart storage
- merge-on-login behavior through auth events
- cart-merge notifications delivered through the framework notification subsystem
- item add/update/remove flows
- totals calculation in services
- database-backed promotion catalog integration
- checkout promotion usage ledgers and usage-limit counter updates
- HTML + JSON parity

### `OrderModule`

- checkout orchestration
- order, order-item, and order-address persistence
- cart snapshotting into orders
- promotion snapshotting and checkout usage recording
- customer-aware promotion limit enforcement for account, email, and segment criteria
- order status and payment-state lifecycles
- payment-method-aware checkout with persisted payment flow, idempotency, provider/external/webhook references, and reconciliation support
- signed payment webhook callback routes with event recording, signature verification, idempotency, and lifecycle reconciliation
- subscription persistence, scheduling, trial handling, renewal orders, retry/dunning, admin pause/resume/cancel, entitlement synchronization, and provider-event reconciliation
- inventory reservations with checkout holds, order attachment, commit/release handling, expiry tracking, and admin/order visibility
- return and exchange workflows with requested/approved/rejected/completed transitions, restock handling, partial refund support, and credit-note generation
- VAT invoice, credit note, packing slip, and return authorization document issuing from admin-native order workflows
- payment manager integration through the first-party compatibility/reference driver
- order lifecycle notifications and listeners
- HTML + JSON parity

## What Is No Longer Missing

These framework/platform areas are now implemented rather than planned:

- starter/auth/admin/commerce application slices
- event dispatcher and listener registration
- queue subsystem and failed-job storage
- notification subsystem
- payment abstraction layer
- provider-specific payment driver coverage for PayPal, Klarna, Swish, Qliro, Walley, credit/debit cards, and crypto
- payment webhook ingestion with signature verification and event idempotency
- top-level config surfaces for notifications, queues, payments, and HTTP security
- passkey/WebAuthn and TOTP support behind framework-native boundaries
- admin-native WebModule page authoring and publishing
- database-backed promotions with checkout usage ledgers
- promotion analytics by code, source, currency, customer, customer segment, and day
- promotion bulk activation, deactivation, deletion, and confirmation workflows
- subscription runtime depth with plans, recurring schedules, dunning, renewal orders, admin lifecycle actions, and provider-event reconciliation
- inventory reservation lifecycle and ledgers
- admin operations panels with queue, notification, event, payment, health, inventory, return/document, and audit drilldown coverage
- return/exchange workflows, partial refunds, restock handling, and credit-note issuing
- VAT/order document issuing for invoices, credit notes, packing slips, and return authorizations

## Remaining Hardening / Environment Work

The framework is in a strong completed state, but a few items remain environment-dependent or are best treated as ongoing hardening rather than missing subsystems:

### 1. Live Environment Breadth

The framework now has both a DB-matrix harness and a local backend verification stack, but real execution against MySQL, PostgreSQL, SQL Server, Redis, Memcached, and extension-gated paths still depends on local or CI services being available and configured.

### 2. Optional Runtime Backends

Redis, Memcache/Memcached, Imagick, and vendor-specific runtime backends are implemented behind framework boundaries, but real verification still depends on the corresponding PHP extensions and services being installed in the target environment.

### 3. Live Integration Breadth

The major framework-level auth and commerce flows are implemented. The next gains are live-provider execution, operational tuning, and project-specific policy breadth:

- richer passkey device metadata and management UX
- broader real-world policy coverage as applications grow
- live subscription provider adapters and live carrier API adapters beyond the framework reference billing/booking/tracking seams
- deeper end-to-end tests around queue-backed notifications, payment-state transitions, promotion/subscription/inventory/return/document behavior in non-SQLite environments
- environment-specific operational tuning for audit retention, queue workers, fulfillment providers, and payment-driver expansion

### 4. CI And Environment Breadth

The repository now includes a stronger GitHub Actions workflow for the default regression suite plus supported MySQL/PostgreSQL matrix execution. The remaining step is repeated live execution on hosted runners and in provisioned local environments:

- GitHub-hosted MySQL/PostgreSQL verification against the updated workflow
- SQL Server verification through the documented local/container path
- Redis, Memcached, and Imagick verification where the corresponding services/extensions are available
- browser and accessibility smoke passes for the public storefront, installer, and admin operator pages

## Recommended Ongoing Verification

For day-to-day framework development:

1. Run `composer test`
2. Run `composer test:db-matrix` when external databases are available
3. Run `composer ops:health`
4. Run `composer ops:ready` when your backing services are provisioned
5. Run `composer release:check` before release candidates
6. Use the console commands to verify operational flows such as migrations, seeds, routes, queue handling, and audit inspection

## Extension Outlook

LangelerMVC no longer needs missing-core work. The natural next layer is application growth and optional platform breadth, for example:

- additional notification channels
- additional payment drivers or project-specific provider adapters
- application-specific policies, events, and workflows
- optional developer generators on top of the now-stable console/runtime base
