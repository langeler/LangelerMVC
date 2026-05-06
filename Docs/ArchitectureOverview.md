# Architecture Overview

LangelerMVC is a custom-built PHP MVC framework designed around structure, modularity, SRP, SoC, and explicit backend boundaries.

The framework is intentionally layered so that each part of the backend has a narrow responsibility:

- the public entrypoint only accepts traffic
- the bootstrap layer prepares the runtime
- the application runtime coordinates core services
- providers and the container resolve infrastructure
- contracts and abstracts define extension seams
- drivers adapt low-level backends
- modules implement application behavior

## Core Principles

- **Extendability**: contracts, providers, abstract base classes, and module boundaries are designed so new application slices and infrastructure drivers can be added without rewriting the core runtime.
- **Maintainability**: cross-cutting behavior is centralized in framework services and traits rather than duplicated across modules and adapters.
- **Readability**: the repository separates framework runtime, reusable infrastructure, and application modules into predictable directories.
- **Security**: the public surface is intentionally thin, configuration is isolated, runtime defaults are controlled in bootstrap, and sensitive subsystems such as cache, session, and crypto are explicit.
- **Performance**: service resolution is lazy where possible, routing and cache are runtime-aware, and the framework avoids doing more work in `Public/index.php` than necessary.
- **Organization**: each layer has a clear job and module folders repeat the same backend shape.

## Layer Map

### Public / Bootstrap

Entry flow:

1. `Public/index.php`
2. `bootstrap/app.php`
3. `App\Core\Bootstrap`
4. `App\Core\App`

Console flow:

1. `console`
2. `bootstrap/console.php`
3. `App\Core\Bootstrap`
4. `App\Console\ConsoleKernel`

Responsibility split:

- `Public/index.php` is the thin front controller.
- `bootstrap/app.php` bridges Composer autoloading into the framework bootstrap.
- `console` is the operational CLI entrypoint for framework maintenance and inspection commands.
- `bootstrap/console.php` bridges Composer autoloading into the console kernel bootstrap.
- `App\Core\Bootstrap` prepares runtime defaults, path registration, installer redirect handling, and environment loading.
- `App\Core\App` boots core services, applies runtime policy, exposes framework health endpoints, dispatches the request, and emits the response.

### Core Runtime

`App/Core` contains the framework runtime services:

- `App`: application lifecycle and dispatch orchestration
- `Bootstrap`: runtime preparation and application creation
- `Config`: runtime-facing config access
- `Container`: reflection-driven dependency resolution
- `Database`: lazy PDO wrapper plus query factories
- `MigrationRunner`: module-aware schema lifecycle runner
- `Router`: route registration, caching, dispatch, and fallback handling
- `SeedRunner`: module-aware data seed lifecycle runner with dependency-aware seed ordering and framework-service resolution
- `Session`: framework session runtime aligned with the config layer
- `ModuleManager`: compatibility alias over the concrete module manager

### Providers

`App/Providers` translates framework services into container-managed entrypoints:

- `CoreProvider`: core service registration and application creation
- `ModuleProvider`: module discovery and registration
- `ExceptionProvider`: typed exception alias resolution
- `CacheProvider`: cache driver resolution
- `CryptoProvider`: crypto driver resolution
- `QueueProvider`: queue driver resolution
- `NotificationProvider`: notification channel resolution
- `PaymentProvider`: payment driver resolution
- `ShippingProvider`: carrier adapter resolution for shipping/service-point/label/tracking/cancellation operations
- `CoreProvider` also exposes the `themes`, `assets`, `frameworkLayers`, and `architecture` services for framework-wide presentation, layer, and architecture inspection

Providers are the framework’s infrastructure composition boundary. They let the runtime depend on contracts and aliases instead of hardcoding driver classes directly.

### Contracts And Abstracts

`App/Contracts` and `App/Abstracts` define the main extension seams.

These cover:

- data helpers such as cache, finder, sanitizer, validator, and crypto
- persistence abstractions such as model, repository, migration, seed, and query
- HTTP abstractions such as controller, request, response, middleware, and service
- presentation abstractions such as presenter, view, resource, and resource collection

These layers are intentionally reusable. Application code is expected to extend them rather than bypass them.

### Drivers

`App/Drivers` contains low-level backend adapters:

- `Caching`: `ArrayCache`, `FileCache`, `DatabaseCache`, `RedisCache`, `MemCache`
- `Cryptography`: `OpenSSLCrypto`, `SodiumCrypto`
- `Notifications`: `MailNotificationChannel`, `DatabaseNotificationChannel`
- `Payments`: `TestingPaymentDriver`, `CardPaymentDriver`, `CryptoPaymentDriver`, `PayPalPaymentDriver`, `KlarnaPaymentDriver`, `SwishPaymentDriver`, `QliroPaymentDriver`, `WalleyPaymentDriver`
- `Shipping`: `PostNordCarrierAdapter`, `InstaboxCarrierAdapter`, `BudbeeCarrierAdapter`, `BringCarrierAdapter`, `DhlCarrierAdapter`, `SchenkerCarrierAdapter`, `EarlyBirdCarrierAdapter`, `AirmeeCarrierAdapter`, `UpsCarrierAdapter`
- `Queue`: `SyncQueueDriver`, `DatabaseQueueDriver`
- `Passkeys`: `TestingPasskeyDriver`, `WebAuthnPasskeyDriver`
- `Session`: `FileSessionDriver`, `DatabaseSessionDriver`, `RedisSessionDriver`, `EncryptedSessionDriver`

The driver layer exists so the framework can present a stable API to the rest of the application while still allowing backend implementation changes.

### Utilities

`App/Utilities` is the shared toolkit layer used by the framework itself:

- `Finders`: file and directory discovery
- `Handlers`: focused utility objects
- `Managers`: concrete system/data services plus compatibility aliases
- `Managers/Async`: event dispatcher, queue manager, and failed-job storage
- `Managers/Commerce`: cart pricing, catalog lifecycle, entitlement, inventory, order lifecycle/document/return, promotion, shipping, and subscription managers
- `Managers/Presentation`: asset, safe HTML, theme, and native template managers
- `Managers/Security`: auth, gate, policy, HTTP signed URL/throttle, and user-provider services
- `Managers/Support`: framework mail, notification, OTP, passkey/WebAuthn, health, audit, and payment service managers
- `Query`: framework SQL builders
- `Sanitation`: sanitizer implementations
- `Validation`: validator implementations
- `Traits`: reusable low-level behavior

This layer is not “miscellaneous helpers”; it is the shared internal toolbox used to keep the framework consistent.

### Modules

`App/Modules` is the application layer.

Each module repeats the same backend shape:

- `Controllers`
- `Middlewares`
- `Migrations`
- `Models`
- `Presenters`
- `Repositories`
- `Requests`
- `Responses`
- `Routes`
- `Seeds`
- `Services`
- `Views`

This repetition is intentional. It gives every domain slice the same internal structure and keeps the app layer predictable as it grows.

## Current Request Lifecycle

The current HTTP request lifecycle is:

1. The web server points traffic at `Public/`.
2. `Public/index.php` loads `bootstrap/app.php`.
3. `Bootstrap` registers framework paths, applies runtime defaults, loads `.env`, and creates the application through the core provider.
4. `App` boots the configured runtime state and resolves the router.
5. `Router` loads module route files, matches the current request, and dispatches the route target.
6. The resolved module/controller pipeline returns a string, array, or response object.
7. `App` emits the final response.

When a request expects JSON, controllers can now negotiate into resource-backed JSON responses rather than always forcing HTML/template rendering.

The first concrete application slice currently follows:

`WebRequest -> HomeController -> PageService -> PagePresenter -> WebView -> WebResponse`

## Implemented Framework Subsystems

The following areas are implemented as framework-level subsystems today:

- bootstrap and application runtime
- dependency injection and provider composition
- configuration and environment override merging
- routing and module route discovery
- operational console and command kernel
- runtime health/readiness/capability reporting
- framework-wide layer introspection through `FrameworkLayerManager`, `php console framework:layers`, and the `framework_layers` release-check gate
- full-repo architecture alignment through `ArchitectureAlignmentManager`, `php console framework:architecture`, and the `architecture_alignment` release-check gate, covering repository contract paths, App boundaries, public/bootstrap thinness, config/data/release parity, tests/CI/scripts, strict class files, manager placement, module shape, native presentation, and docs
- migration and seed lifecycle management
- framework-managed audit logging for sensitive flows
- session runtime
- file, database, and redis session driver adapters
- encrypted persisted session payload support
- session-backed authentication, RBAC, TOTP/recovery-code 2FA, and passkey/WebAuthn support
- signed URL and throttling support
- sanitation and validation APIs
- HTTP/MVC abstraction layer
- presentation resource / negotiated JSON layer
- completed view/presenter/template layer with shared layouts, partials, and components
- canonical `.vide` templates authored in native directives rather than embedded raw PHP
- `.vide` layout sections/stacks for named regions while preserving the default `$content` layout flow
- framework-wide Bootstrap-compatible theme, HTML helper, and asset management with tracked source/public CSS and JavaScript assets, cache-busted asset URLs, preload helpers, and named bundles
- database layer and SQL/query builders
- cache subsystem
- crypto subsystem
- async events and queues
- notification subsystem
- payment abstraction subsystem
- payment method/flow compatibility introspection, redirect/customer-action handling, signed/idempotent webhook ingestion, reconciliation support, and plug-and-play provider drivers for card, PayPal, Klarna, Swish, Qliro, Walley, and crypto flows
- mail, OTP, and passkey/WebAuthn service boundaries
- file, finder, iterator, and reflection utilities
- model and repository foundations

These subsystems are also covered by the current regression suite under `Tests/Framework`.

## Concrete Application Surface Today

The application layer now ships with completed first-party reference slices and remains intentionally extensible.

Current concrete state:

- `WebModule` is the database-backed starter/content slice with admin-native authoring and publishing.
- `UserModule` is the full identity/platform slice.
- `AdminModule` is the protected management slice.
- `ShopModule`, `CartModule`, and `OrderModule` complete the first commerce stack.
- `WebModule` has a controller, request, service, presenter, view, response, model, repository, route file, migration, and seed.
- `UserModule` now provides registration, login, logout, password reset, email verification, RBAC, TOTP/recovery-code 2FA with trusted devices, and passkey/WebAuthn flows.
- `AdminModule` now provides dashboard, user, role/permission, page authoring, catalog, promotion, cart, order, health/readiness, and framework-inspection flows.
- `ShopModule` provides catalog listing/detail flows with products, categories, pricing, publish state, and tracked public demo media.
- `CartModule` provides guest/auth cart persistence, merge-on-login behavior, promotion records, and usage ledgers.
- `OrderModule` provides payment-method-aware checkout orchestration, order snapshots, promotion usage recording, payment-state handling, signed/idempotent webhook reconciliation, and lifecycle notifications.
- Shared templates now live in `App/Templates/Layouts`, `App/Templates/Pages`, `App/Templates/Partials`, and `App/Templates/Components` with `.vide` as the canonical native template extension. `.lmv` and `.php` remain supported as compatibility fallbacks, but the first-party `.vide` tree is now maintained without raw PHP tags in source.

This means the framework now has both a completed platform base and a real first-party application surface exercising it.

## Extension Points

The most important extension seams today are:

- **Modules**: add real business slices under `App/Modules/*`.
- **Persistence**: add migrations, seeds, repositories, and real schemas on top of the existing database/query/model base.
- **Console**: extend the operational command set or add scaffolding/generator support when a project needs it.
- **Views**: extend the shared presentation surface with new layouts, pages, partials, and components without duplicating module orchestration.
- **Themes**: swap or extend `Config/theme.php`, `App/Resources/*`, and `Public/assets/*` without changing module controllers.
- **Commerce Managers**: extend cart pricing, promotions, inventory, order lifecycle, shipping, subscriptions, documents, and returns under `App/Utilities/Managers/Commerce` while legacy support paths remain aliases.
- **Drivers**: add more concrete infrastructure backends through providers and contracts.
- **Validation / Sanitization**: extend schema methods and rules through the existing APIs.
- **Caching / Crypto**: change backends without changing higher-level application code.
- **Support Services**: extend authentication, authorization, notifications, and payments on top of the existing manager and provider boundaries.
- **Payments**: add project-specific drivers later against the now-stable capability contract without pushing vendor SDK concerns into framework core.
- **Async**: add listeners, jobs, queue drivers, and failed-job strategies without rewriting runtime or modules.
- **Layer Inspection**: update `FrameworkLayerManager` whenever a new required framework layer or release-critical surface is introduced.
- **Architecture Alignment**: update `ArchitectureAlignmentManager` and `Docs/ArchitectureAlignment.md` whenever strict class-file, canonical manager, module-shape, presentation-native, or documentation source-of-truth rules intentionally change.

## Post-Release Architectural Boundaries

The framework no longer has major missing platform subsystems. After the `v1.0.0` source release, the main architectural work is environment breadth and optional project expansion:

- live multi-database verification when external services are available
- additional notification channels or payment drivers
- optional developer generators on top of the stable console layer
- deeper application-specific policies and workflows as real products are built
