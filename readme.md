![Logo](./logo.jpeg)

# LangelerMVC

[![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-blue.svg)](https://www.php.net/)

LangelerMVC is a custom-built PHP MVC framework designed with a strong focus on structure, modularity, and best practices, including SRP, SoC, and OOP principles.

## Verification Snapshot

As of `2026-04-11`:

- Verified on PHP `8.4.12`
- `composer test` passes with `OK (81 tests, 1980 assertions)`
- The framework runtime, console, schema lifecycle, HTTP/MVC/presentation, validation, query/persistence, cache, crypto, async, notification, payment, auth, and utility subsystems are implemented and regression-tested
- `WebModule`, `UserModule`, `AdminModule`, `ShopModule`, `CartModule`, and `OrderModule` are implemented first-party modules
- SQLite is verified in the default suite, and a database-matrix harness is available for MySQL, PostgreSQL, and SQL Server verification

## Current State

- The framework backend is no longer just scaffolding. Bootstrap, runtime, container, config, routing, session, validation, sanitization, cache, crypto, SQL/query, file/finder/iterator/reflection, persistence, async, notifications, payments, and security utilities are implemented and regression-tested.
- The framework now includes a first-party operational console, module-aware migration/seed runners, resource-based JSON response support, first-party file/database/redis session drivers with optional encrypted payload storage, framework-native mail/OTP/passkey boundaries, event dispatching, queue processing, notification channels, payment driver abstractions, and HTTP signed URL/throttling support.
- The shared presentation layer is now completed around default-layout-aware views, presenter export helpers, structured resources/resource collections, reusable `Layouts`, `Pages`, `Partials`, and `Components`, plus storefront-ready product media rendering.
- `WebModule` is the reference starter slice and now runs database-backed by default through framework-managed `pages` migrations, seeds, repositories, presenters, resources, views, and responses.
- `UserModule` now provides the first full identity/platform slice with session authentication, password reset, email verification, RBAC foundations, TOTP-based 2FA, recovery codes, and passkey/WebAuthn flows for both HTML and JSON endpoints.
- `AdminModule` now provides the management slice for dashboard, user/role/permission management, module visibility, cache/config/session inspection, catalog visibility, cart visibility, order visibility, and operational health.
- `ShopModule`, `CartModule`, and `OrderModule` now provide the first full commerce stack for catalog, cart, checkout, payment-state handling, and order lifecycle flows with HTML + JSON parity.
- Mail, OTP, WebAuthn, notifications, queues, and payments are all consumed through framework-native contracts and managers rather than module-local third-party calls.

## Design Goals

- Extendability through contracts, abstract base classes, providers, and modular application boundaries.
- Maintainability through SRP, SoC, and focused responsibilities in the core runtime and module layers.
- Readability through explicit folder boundaries, typed abstractions, and framework-level regression coverage.
- Security through a thin public entrypoint, environment-aware bootstrap, configuration isolation, session hardening, and a safer Apache public config.
- Performance through lazy service resolution, config caching in memory, route caching, and a clear separation between public assets and application resources.
- Organization through a predictable `App/` layout and module-first backend architecture.

## Request Lifecycle

The current HTTP flow is:

1. `Public/index.php` boots the application through `bootstrap/app.php`.
2. `App\Core\Bootstrap` registers framework paths, applies runtime defaults, loads `.env`, and creates the application.
3. `App\Core\App` boots the framework, resolves config and router services, applies runtime policy, and dispatches the request.
4. `App\Core\Router` loads module route files and dispatches the matched controller action.
5. Concrete module classes handle the request through the framework pipeline.

In the current starter slice, `WebModule` follows:

`WebRequest -> HomeController -> PageService -> PagePresenter -> WebView -> WebResponse`

## Architecture Overview

### `App/`

- `Abstracts/`: reusable framework base classes for data, database, HTTP, and presentation concerns.
- `Contracts/`: interface surface for the abstractions and core layer, including console, presentation resource, session, and support contracts.
- `Core/`: framework runtime services such as `App`, `Bootstrap`, `Config`, `Container`, `Database`, `MigrationRunner`, `Router`, `SeedRunner`, and `Session`.
- `Drivers/`: low-level pluggable adapters. Caching, cryptography, and session drivers are present.
- `Exceptions/`: typed framework exception classes grouped by concern.
- `Modules/`: application modules. `WebModule`, `UserModule`, `AdminModule`, `ShopModule`, `CartModule`, and `OrderModule` are implemented first-party slices.
- `Providers/`: container/provider wiring for core, cache, crypto, exceptions, and modules.
- `Resources/`: source asset workspace that belongs to the application layer.
- `Templates/`: shared PHP template files used by module views, including layouts, pages, partials, and reusable components.
- `Utilities/`: shared traits, handlers, managers, finders, query helpers, validators, sanitizers, and support managers such as mail, OTP, and passkeys/WebAuthn.

### Other Root Folders

- `Config/`: runtime configuration arrays loaded by the config facade and merged with `.env` overrides at runtime.
- `Data/`: standalone SQL reference files kept as reference material beside the framework-managed migration system.
- `Docs/`: current architecture and structure docs, plus older reference materials kept in the repository.
- `Public/`: the public document root, front controller, Apache config, and public asset folders, including tracked storefront demo imagery.
- `console`: the first-party CLI entrypoint for operational framework commands.
- `Services/`: workspace for cross-application service composition outside a specific module.
- `Storage/`: cache, logs, secure keys, sessions, and uploads.
- `Tests/`: framework regression coverage, optional `Unit` and `Integration` suite buckets, and a separate DB-matrix harness.
- `autoload.php`: legacy fallback autoload helper. The primary bootstrap path uses Composer through `bootstrap/app.php`.

For a deeper architecture walkthrough, see [`Docs/ArchitectureOverview.md`](./Docs/ArchitectureOverview.md).

## Installation

### Requirements

- PHP 8.4+
- Composer

### Setup

```bash
git clone https://github.com/langeler/LangelerMVC.git
cd LangelerMVC
composer install
cp .env.example .env
```

Adjust `.env` values as needed for your environment. The framework can boot without a live database connection, but database-backed modules will of course require valid DB settings.

## Running The Project

### Built-in PHP Server

```bash
php -S 127.0.0.1:8000 -t Public Public/index.php
```

### Apache

Point the document root at `Public/` and use [`Public/.htaccess`](./Public/.htaccess) for front-controller routing and baseline public protections.

### Framework Console

```bash
php console list
php console migrate
php console seed WebModule
php console route:list
```

## Configuration Notes

- `.env` provides environment-specific overrides.
- `Config/*.php` files provide the tracked runtime configuration surface.
- `Config/auth.php` contains the framework auth baseline, including RBAC, OTP/TOTP, and passkey/WebAuthn settings.
- `Config/webmodule.php` controls the current `WebModule` content source and defaults to `CONTENT_SOURCE=database`.
- `Config/notifications.php`, `Config/queue.php`, `Config/payment.php`, and `Config/http.php` provide the top-level settings for notifications, queue drivers, payment drivers, throttling, and signed URLs.
- Session drivers support `native`, `file`, `database`, and `redis`, with `native` remaining the tracked default.
- `Config/session.php` also supports `ENCRYPT=true`, which encrypts persisted session payloads at rest through the configured crypto subsystem while keeping legacy plaintext sessions readable during transition.
- Session files are stored in `Storage/Sessions` by default when using the native/files-backed modes.

## Testing

Run the current regression suite with:

```bash
composer test
composer test:db-matrix
composer test:mysql
composer test:pgsql
composer test:sqlsrv
```

The active default regression tests live in `Tests/Framework`. `Tests/DbMatrix` contains the external-driver verification harness, while `Tests/Unit` and `Tests/Integration` remain available for additional isolated and cross-layer suites when a project needs them.

## Structure Docs

- [`Docs/README.md`](./Docs/README.md): documentation index and reading order.
- [`Docs/ArchitectureOverview.md`](./Docs/ArchitectureOverview.md): framework architecture, runtime flow, subsystem map, and extension points.
- [`Docs/FrameworkStatus.md`](./Docs/FrameworkStatus.md): current implementation status, remaining hardening areas, and environment-dependent verification notes.
- [`Docs/FolderStructure.md`](./Docs/FolderStructure.md): current architecture by layer and responsibility.
- [`Docs/ModulesStructure.md`](./Docs/ModulesStructure.md): module layout, conventions, and current module status.
- [`Docs/CompleteStructure.md`](./Docs/CompleteStructure.md): full current repository tree, excluding `.git` and `vendor`.
- [`Docs/DatabaseMatrixTesting.md`](./Docs/DatabaseMatrixTesting.md): how to run the MySQL/PostgreSQL/SQL Server verification harness locally.
- [`Docs/SanitationValidationAPI.md`](./Docs/SanitationValidationAPI.md): schema contract for sanitizers and validators.
- [`Docs/UtilitiesTraitsOverview.md`](./Docs/UtilitiesTraitsOverview.md): practical overview of the trait surface.
- [`Docs/UtilitiesTraitsReference.md`](./Docs/UtilitiesTraitsReference.md): generated trait reference.

## Platform Status

LangelerMVC now ships as a complete first-party platform framework with:

- a thin bootstrap/runtime boundary
- provider-driven composition and lazy infrastructure
- validated session/auth/RBAC/TOTP/passkey support
- cache, crypto, SQL/query, migration, and seed subsystems
- async events, queues, notifications, and payment abstractions
- completed HTML + JSON presentation parity across first-party modules
- a database-backed starter module plus user/admin/shop/cart/order slices

The main remaining work is hardening and environment breadth rather than missing platform pieces: broader live DB-matrix execution, Redis/Memcache-backed runtime verification where those services exist, and ongoing domain/policy refinement as real applications are built on top of the framework.

## Support

- Issues: [github.com/langeler/LangelerMVC/issues](https://github.com/langeler/LangelerMVC/issues)
- Source: [github.com/langeler/LangelerMVC](https://github.com/langeler/LangelerMVC)
- Wiki: [github.com/langeler/LangelerMVC/wiki](https://github.com/langeler/LangelerMVC/wiki)

## License

This project is licensed under the MIT License.
