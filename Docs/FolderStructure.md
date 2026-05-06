# Folder Structure

This document describes the current LangelerMVC repository as it exists in code today. It is intentionally based on the tracked filesystem and implemented classes rather than on speculative future layers.

## Front Controller And Bootstrap

The primary entry flow is:

1. `Public/index.php`
2. `bootstrap/app.php`
3. `App/Core/Bootstrap.php`
4. `App/Core/App.php`

This keeps the public entrypoint intentionally thin while the framework bootstrap owns environment loading, path registration, and application composition.

## `App/` Layer Map

### `App/Abstracts`

Framework base classes for:

- data handling
- persistence
- HTTP orchestration
- presentation
- support workflows such as `Mailable`

These are extension points, not application-specific implementations.

### `App/Contracts`

Interfaces for the abstract and concrete framework surfaces. This layer now also includes console, session, presentation resource, operational, and support-service contracts.

### `App/Console`

Operational CLI surface for the framework:

- `ConsoleKernel.php`: command registration and argument dispatch
- `Commands/`: first-party operational commands such as migrations, seeds, routes, cache, config, module inspection, health checks, audit inspection, queue operations, and notification/event visibility

### `App/Core`

The framework runtime layer:

- `App.php`: boots the framework, applies runtime policy, dispatches the request, and emits the response.
- `Bootstrap.php`: prepares the environment and creates the application.
- `Config.php`: runtime-facing configuration facade.
- `Container.php`: reflection-driven dependency container.
- `Database.php`: connection and query execution layer.
- `MigrationRunner.php`: module-aware schema lifecycle runner.
- `ModuleManager.php`: compatibility wrapper for module discovery.
- `Router.php`: route registration, route cache loading, and dispatch.
- `Schema/`: schema blueprint support for framework migrations.
- `SeedRunner.php`: module-aware seed lifecycle runner with dependency-aware ordering and framework-service resolution.
- `Session.php`: framework session runtime.

### `App/Drivers`

Low-level adapters for pluggable infrastructure concerns:

- `Caching/`: concrete cache drivers (`ArrayCache`, `FileCache`, `DatabaseCache`, `RedisCache`, `MemCache`)
- `Cryptography/`: concrete crypto drivers
- `Notifications/`: concrete notification channel drivers
- `Payments/`: concrete payment drivers (`TestingPaymentDriver`, `CardPaymentDriver`, `CryptoPaymentDriver`, `PayPalPaymentDriver`, `KlarnaPaymentDriver`, `SwishPaymentDriver`, `QliroPaymentDriver`, `WalleyPaymentDriver`)
- `Shipping/`: concrete carrier adapters (`PostNordCarrierAdapter`, `InstaboxCarrierAdapter`, `BudbeeCarrierAdapter`, `BringCarrierAdapter`, `DhlCarrierAdapter`, `SchenkerCarrierAdapter`, `EarlyBirdCarrierAdapter`, `AirmeeCarrierAdapter`, `UpsCarrierAdapter`)
- `Passkeys/`: concrete passkey drivers
- `Queue/`: concrete queue drivers
- `Session/`: concrete file, database, redis, and encrypted session drivers

### `App/Exceptions`

Typed exceptions grouped by concern so failures stay explicit and easier to debug:

- general app/container/config errors
- data errors
- database errors
- HTTP errors
- iterator errors
- presentation errors
- routing errors

### `App/Helpers`

Available for focused helper classes that genuinely belong outside traits, managers, handlers, and modules. It remains tracked as an explicit extension seam even when no helper class is currently needed.

### `App/Modules`

The application layer is module-first. Each module follows the same backend shape:

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

`WebModule`, `UserModule`, `AdminModule`, `ShopModule`, `CartModule`, and `OrderModule` are implemented. The `README.md` files that remain inside module subfolders now act as living directory notes so each repeated backend surface stays explicit even when a specific folder only needs a few concrete classes today.

### `App/Providers`

Container/provider wiring for core, cache, crypto, exception, theming, payment, shipping, queue, notification, and module registration.

### `App/Resources`

Source asset workspace that belongs to the application layer rather than the public web root. The framework theme source lives here and is mirrored to tracked public assets for release use.

### `App/Templates`

Shared native `.vide` templates used by concrete view classes. The framework now uses:

- `Layouts/WebShell.vide`
- `Layouts/UserShell.vide`
- `Layouts/AdminShell.vide`
- module pages under `Pages/`
- reusable shared fragments under `Partials/`
- reusable shared building blocks under `Components/`

`.lmv` and `.php` templates remain supported as compatibility fallbacks, but `.vide` is now the canonical framework-native presentation surface.

### `App/Utilities`

Shared framework tooling. This is the main reusable backend toolbox and currently contains:

- `Finders/`: directory and file discovery
- `Handlers/`: focused data/system helper objects
- `Managers/`: concrete manager implementations and compatibility aliases
- `Managers/Async/`: event dispatcher, queue manager, and failed-job storage
- `Managers/Commerce/`: cart pricing, catalog lifecycle, entitlement, inventory, order lifecycle/document/return, promotion, shipping, and subscription managers
- `Managers/Presentation/`: asset, safe HTML, theme, and template managers for `.vide`, public assets, layout globals, and escaped helper output
- `Managers/Support/`: framework-native mail, notification, OTP, passkey/WebAuthn, health, audit, and payment service managers
- `Query/`: query builder helpers
- `Sanitation/`: sanitization implementations
- `Traits/`: reusable low-level behavior
- `Validation/`: validator implementations

Important note:

- `App/Utilities/Managers/System/*` and `App/Utilities/Managers/Data/*` contain concrete implementations for several services.
- Some flat manager classes in `App/Utilities/Managers/*` exist as convenience or compatibility wrappers over those deeper implementations.
- Legacy support paths such as `App/Support/Commerce/*Manager.php` and `App/Support/Theming/ThemeManager.php` are retained as compatibility aliases; new framework code should use the canonical manager namespaces under `App/Utilities/Managers/*`.

## Other Top-Level Folders

### `Config`

Tracked runtime configuration arrays. The framework loads these through `SettingsManager` and `Config`, then merges environment overrides from `.env` at runtime.

Notable current files include:

- `auth.php`
- `commerce.php`
- `http.php`
- `mail.php`
- `notifications.php`
- `payment.php`
- `queue.php`
- `session.php`
- `theme.php`
- `webmodule.php`

`session.php` now also exposes `ENCRYPT`, which enables at-rest session payload encryption through the framework crypto subsystem.

### `Data`

Release-reference SQLite schema snapshots generated from the framework and first-party module migrations. They remain in the repository for review, onboarding, and release auditing, but the authoritative runtime schema still lives under `App/Framework/Migrations`, `App/Core`, `App/Abstracts/Database`, and module `Migrations/` / `Seeds/`.

Current grouped snapshots:

- `Framework.sql`
- `Web.sql`
- `Users.sql`
- `Products.sql`
- `Carts.sql`
- `Orders.sql`
- `README.md`

### `Docs`

Current documentation plus historical PDF/RTF reference material.

Primary current docs:

- `README.md`
- `ArchitectureOverview.md`
- `DeploymentAndUpgrade.md`
- `FrameworkStatus.md`
- `FolderStructure.md`
- `ModulesStructure.md`
- `CompleteStructure.md`
- `RepositoryMetadata.md`
- `SanitationValidationAPI.md`
- `Wiki/`

### `Public`

The web-facing document root:

- `index.php` is the thin front controller.
- `.htaccess` contains the Apache rewrite and baseline public protections.
- `assets/` contains public asset directories, including tracked demo storefront images under `assets/images/` and the release-tracked framework theme CSS/JS files.

### `.github/workflows`

Tracked CI automation for framework verification. The repository now ships with a default regression workflow plus supported MySQL/PostgreSQL matrix execution, explicit service readiness waits, and DB service log artifacts on matrix failures.

### `CONTRIBUTING.md`

Contributor workflow, verification expectations, and coding standards for framework changes.

### `SECURITY.md`

Supported-version and vulnerability disclosure guidance for the framework.

### `Services`

Reserved for cross-application service composition that does not belong to a specific module. The current implementation keeps concrete services inside modules.

### `Storage`

Runtime storage:

- `Cache/`
- `Logs/`
- `Secure/`
- `Sessions/`
- `Uploads/`

### `Tests`

Testing surface:

- `Framework/`: current regression suite for the framework/backend itself
- `DbMatrix/`: opt-in external database and Redis/Memcached runtime backend harnesses
- `Unit/`: optional bucket for isolated class tests
- `Integration/`: optional bucket for cross-layer tests

### `console`

The first-party framework CLI entrypoint. It boots `bootstrap/console.php` and dispatches the command kernel.

### `docker-compose.verify.yml`

Local backend verification stack for MySQL, PostgreSQL, SQL Server, Redis, and Memcached.

### `autoload.php`

Legacy fallback autoload helper. It is still tracked, but the primary application bootstrap uses Composer through `bootstrap/app.php`.

## Notes

- Directory `README.md` files are intentionally kept in extension-oriented folders so the repository can communicate the full implemented architecture without invisible directories.
- The canonical current documentation entrypoints are `Docs/README.md`, `Docs/ArchitectureOverview.md`, and `Docs/FrameworkStatus.md`.
- `Docs/OperationsGuide.md` and `Docs/DatabaseMatrixTesting.md` now document the production-style verification and operator-facing runtime surfaces.
