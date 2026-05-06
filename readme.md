![Logo](./logo.jpeg)

# LangelerMVC

[![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-blue.svg)](https://www.php.net/)
[![Release](https://img.shields.io/github/v/release/langeler/LangelerMVC?label=release)](https://github.com/langeler/LangelerMVC/releases/tag/v1.0.0)

LangelerMVC is a custom-built PHP MVC framework designed with a strong focus on structure, modularity, and best practices, including SRP, SoC, and OOP principles. It ships as a complete first-party platform baseline with native `.vide` templates, admin operations, commerce, payments, shipping, subscriptions, promotions, inventory, returns, VAT/order documents, health checks, audit tooling, and a guided installer.

## Published Release

The current public framework source release is [`v1.0.0`](https://github.com/langeler/LangelerMVC/releases/tag/v1.0.0), published on `2026-05-01`.

This release is framework/package ready: the core runtime, first-party modules, installer, provider seams, docs, and release gates are complete and verified. Project go-live remains deployment-specific because live payment/subscription/carrier credentials, seller VAT/legal settings, optional matrix extensions, and browser/accessibility passes belong to each installed application environment.

## Verification Snapshot

As of `2026-05-01`:

- Verified on PHP `8.4.12`
- `composer test` passes with `OK (154 tests, 3288 assertions)`
- `composer ops:health` returns a healthy liveness response
- `composer validate --no-check-publish` passes
- `composer release:check` returns `status=200`
- `php console framework:layers` reports all required framework layer paths present
- The framework runtime, console, schema lifecycle, HTTP/MVC/presentation, validation, query/persistence, cache, crypto, async, notification, payment, auth, commerce, fulfillment, inventory, return/document, and utility subsystems are implemented and regression-tested
- `WebModule`, `UserModule`, `AdminModule`, `ShopModule`, `CartModule`, and `OrderModule` are implemented first-party modules
- SQLite is verified in the default suite, and a database/runtime matrix harness is available for MySQL, PostgreSQL, SQL Server, Redis, and Memcached verification where services and PHP extensions exist

## Current State

- The framework backend is no longer just scaffolding. Bootstrap, runtime, container, config, routing, session, validation, sanitization, cache, crypto, SQL/query, file/finder/iterator/reflection, persistence, async, notifications, payments, and security utilities are implemented and regression-tested.
- The framework now includes a first-party operational console, module-aware migration/seed runners, resource-based JSON response support, first-party file/database/redis session drivers with optional encrypted payload storage, framework-native mail/OTP/passkey boundaries, event dispatching, queue processing, notification channels, payment driver abstractions, and HTTP signed URL/throttling support.
- The payment layer now exposes a plug-and-play compatibility surface with driver capability/readiness introspection, payment-method and flow discovery, provider-specific env/installer settings, redirect/customer-action handling, asynchronous reconciliation hooks, provider/external references, and idempotency-aware checkout persistence.
- First-party payment drivers now ship for `Credit / Debit Card`, `PayPal`, `Klarna`, `Swish`, `Qliro`, `Walley`, and `Crypto`, plus the framework testing/reference driver.
- Shipping now exposes a plug-and-play carrier adapter surface for PostNord, Instabox, Budbee, Bring, DHL, Schenker, Early Bird, Airmee, UPS, service-point lookup, booking, labels, tracking, cancellation, and Mina Paket handoff metadata.
- Commerce operational managers are centralized under `App\Utilities\Managers\Commerce`, covering cart pricing, catalog lifecycle, entitlement, inventory, order lifecycle/document/return, promotion, shipping, and subscriptions while legacy support paths remain compatibility aliases.
- The presentation layer now includes centralized asset, safe HTML, theme, and template managers under `App\Utilities\Managers\Presentation`, with Bootstrap-compatible light, dark, and system modes backed by `Config/theme.php`, installer settings, shared view globals, tracked public assets, versioned asset URLs, preload helpers, named asset bundles, and script-safe JSON output.
- The framework now exposes a layer-introspection manager and `framework:layers` console command so public/bootstrap, core, provider, contract, MVC, presentation, data, security, driver, utility, module, installer, console, and release/docs/data surfaces stay enforceable.
- The runtime now also exposes first-party liveness/readiness health endpoints, capability reporting, and framework-managed audit logging for sensitive operational flows.
- Seed execution now resolves repository and framework-service dependencies consistently, and the remaining async/auth/commerce payload boundaries now serialize through the framework helpers rather than ad hoc native calls.
- Commerce money formatting and auth-side encoding/hash fallbacks are now centralized through framework helpers instead of being duplicated across services and repositories.
- The shared presentation layer is now completed around default-layout-aware views, section/yield/stack composition, presenter export helpers, structured resources/resource collections, a framework-native `.vide` template engine, reusable `Layouts`, `Pages`, `Partials`, and `Components`, plus storefront-ready product media rendering.
- The canonical `.vide` template tree is now authored fully in native directives without raw PHP tags, with regression coverage enforcing that standard across first-party templates.
- `WebModule` is the reference starter slice and now runs database-backed by default through framework-managed `pages` migrations, seeds, repositories, presenters, resources, views, and responses.
- `UserModule` now provides the first full identity/platform slice with session authentication, password reset, email verification, RBAC foundations, TOTP-based 2FA, trusted-device support, recovery codes, and passkey/WebAuthn flows for both HTML and JSON endpoints.
- `AdminModule` now provides the management slice for dashboard, user/role/permission management, module visibility, cache/config/session inspection, catalog/cart/order visibility, WebModule page authoring, promotion analytics and bulk workflows, structured operations panels, inventory ledgers, returns/exchanges, order documents, runtime health/readiness, and audit-aware operations visibility.
- `AdminModule` intentionally remains orchestration-first on persistence: it manages existing framework and domain state instead of introducing a duplicate admin-owned data model.
- `ShopModule`, `CartModule`, and `OrderModule` now provide the first full commerce stack for catalog, cart, checkout, promotions, subscriptions, digital/virtual entitlements, pickup/pre-order/shipping fulfillment, inventory reservations, payment-state handling, returns/exchanges, VAT/order documents, order lifecycle flows, cart-merge notifications, and catalog activity notifications with HTML + JSON parity.
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
- `Drivers/`: low-level pluggable adapters for cache, crypto, notifications, payments, passkeys, queueing, shipping, and sessions.
- `Exceptions/`: typed framework exception classes grouped by concern.
- `Modules/`: application modules. `WebModule`, `UserModule`, `AdminModule`, `ShopModule`, `CartModule`, and `OrderModule` are implemented first-party slices.
- `Providers/`: container/provider wiring for core, cache, crypto, notifications, payments, shipping, queueing, theming, exceptions, and modules.
- `Resources/`: source asset workspace that belongs to the application layer, including the canonical framework theme CSS/JS source.
- `Templates/`: shared native `.vide` template files used by module views, including layouts, pages, partials, and reusable components. `.lmv` and `.php` remain readable as compatibility fallbacks.
- `Utilities/`: shared traits, handlers, finders, query helpers, validators, sanitizers, and manager sublayers, including `Managers/Commerce` for ecommerce operations and `Managers/Presentation` for assets, safe HTML helpers, themes, and `.vide` templates.

### Other Root Folders

- `Config/`: runtime configuration arrays loaded by the config facade and merged with `.env` overrides at runtime.
- `Data/`: release-reference SQLite schema snapshots generated from the framework-managed migration system.
- `Docs/`: current architecture and structure docs, plus older reference materials kept in the repository.
- `Public/`: the public document root, front controller, Apache config, tracked theme CSS/JS, and public asset folders, including tracked storefront demo imagery.
- `Public/install/index.php`: the installer entrypoint for first-run setup.
- `console`: the first-party CLI entrypoint for operational framework commands.
- `Services/`: workspace for cross-application service composition outside a specific module.
- `Storage/`: cache, logs, secure keys, sessions, and uploads.
- `Tests/`: framework regression coverage, optional `Unit` and `Integration` suite buckets, and a separate DB-matrix harness.
- `autoload.php`: legacy fallback autoload helper. The primary bootstrap path uses Composer through `bootstrap/app.php`.

For a deeper architecture walkthrough, see [`Docs/ArchitectureOverview.md`](./Docs/ArchitectureOverview.md). For the executable layer audit and mature-framework comparison, see [`Docs/FrameworkWideLayerEvaluation.md`](./Docs/FrameworkWideLayerEvaluation.md).

## Installation

### Requirements

- PHP 8.4+
- Composer

### Setup

```bash
git clone https://github.com/langeler/LangelerMVC.git
cd LangelerMVC
composer install
php -S 127.0.0.1:8000 -t Public Public/index.php
```

Then open [http://127.0.0.1:8000](http://127.0.0.1:8000). If the framework is not installed yet, `App\Core\Bootstrap` automatically redirects to the built-in installation wizard at `/install/index.php`.

The installer wizard now handles:

- a guided step-based setup experience with progress navigation, runtime readiness panels, and no-JS fallback behavior
- application name, URL, locale, and runtime defaults
- database driver and connection setup
- storage preparation
- migration + seed execution
- administrator provisioning
- default database-backed `WebModule` setup
- payment driver, method-family, flow defaults, provider live endpoint fields, and webhook-secret placeholders for the first commerce-ready baseline
- carrier adapter defaults for Swedish-first shipping, tracking, service-point, and label flows
- framework-wide theme defaults, public theme assets, and light/dark/system toggle behavior
- commerce fulfillment, shipping, subscription, inventory reservation, return, and order-document defaults

Manual `.env` editing is still supported, and `.env.example` remains the tracked baseline, but the intended production-first setup path is now the installer wizard rather than editing config files before first boot.

## Running The Project

### Built-in PHP Server

```bash
php -S 127.0.0.1:8000 -t Public Public/index.php
```

If the application is not installed yet, the first request opens the installer wizard automatically.

### Apache

Point the document root at `Public/` and use [`Public/.htaccess`](./Public/.htaccess) for front-controller routing and baseline public protections.

### Framework Console

```bash
php console list
php console health:check
php console health:check ready
php console framework:doctor
php console framework:doctor --strict
php console audit:list --limit=25
php console migrate
php console module:make Blog
php console seed WebModule
php console route:list
```

## Operational Verification

For a clean production-style verification pass, the framework now ships with:

- `composer verify:platform` for the default regression suite plus a health check
- `composer release:check` for release docs, env parity, `Data/*.sql` schema references, critical routes, module/payment/theme surfaces, commerce breadth, template accessibility, matrix readiness, and live-integration warnings
- `composer verify:release` for Composer metadata validation, the default regression suite, health liveness, and release gate execution
- `.github/workflows/php.yml` for default regression and supported DB-matrix CI coverage
- `docker-compose.verify.yml` for local MySQL/PostgreSQL/SQL Server/Redis/Memcached verification
- workflow-level platform checks, explicit MySQL/PostgreSQL readiness waits, target diagnostics, and DB service log artifacts on CI failures

Typical local backend bring-up:

```bash
docker compose -f docker-compose.verify.yml up -d
composer test:db-matrix
composer test:runtime-backends
composer ops:health
composer ops:ready
```

For local compose-based verification, use the standard service ports exposed by `docker-compose.verify.yml`:

- MySQL: `3306`
- PostgreSQL: `5432`
- SQL Server: `1433`
- Redis: `6379`
- Memcached: `11211`

The GitHub Actions workflow uses isolated service-port mappings for hosted runners and prints the selected DB target before executing the matrix job.

## Configuration Notes

- `.env` provides environment-specific overrides.
- `Config/*.php` files provide the tracked runtime configuration surface.
- `Config/auth.php` contains the framework auth baseline, including RBAC, OTP/TOTP, and passkey/WebAuthn settings.
- `Config/webmodule.php` controls the current `WebModule` content source and defaults to `CONTENT_SOURCE=database`.
- `Config/notifications.php`, `Config/queue.php`, `Config/payment.php`, and `Config/http.php` provide the top-level settings for notifications, queue drivers, payment drivers, throttling, and signed URLs.
- `Config/payment.php` now defines the default payment driver, currency, payment method family, and payment flow.
- `Config/payment.php` now ships first-party driver entries for `testing`, `card`, `crypto`, `paypal`, `klarna`, `swish`, `qliro`, and `walley`.
- `Config/commerce.php` defines commerce totals, fulfillment, shipping, subscription, inventory reservation, return, and order-document settings.
- `Config/theme.php` defines framework-wide theme defaults, light/dark/system mode policy, and public CSS/JS asset paths.
- The provider-specific payment drivers support the framework payment taxonomy without vendor SDKs in core:
  - `card`: credit/debit card flows
  - `paypal`: wallet/card flows
  - `klarna`: BNPL flows
  - `swish`: Swedish local-instant flows
  - `qliro`: card / BNPL / local-instant / bank-transfer flows
  - `walley`: BNPL flows
  - `crypto`: BTC/ETH-style crypto invoice and reconciliation flows
- Live provider execution still depends on merchant credentials, provider onboarding, and environment readiness. The framework ships the driver boundary, capability model, reference behavior, and live configuration surface.
- Session drivers support `native`, `file`, `database`, and `redis`, with `native` remaining the tracked default.
- `Config/session.php` also supports `ENCRYPT=true`, which encrypts persisted session payloads at rest through the configured crypto subsystem while keeping legacy plaintext sessions readable during transition.
- Session files are stored in `Storage/Sessions` by default when using the native/files-backed modes.

## Testing

Run the current regression suite with:

```bash
composer test
composer test:db-matrix
composer test:runtime-backends
composer test:mysql
composer test:pgsql
composer test:sqlsrv
composer ops:health
composer ops:ready
composer ops:audit
composer verify:platform
composer release:check
composer verify:release
```

The active default regression tests live in `Tests/Framework`. `Tests/DbMatrix` contains the external-driver verification harness, while `Tests/Unit` and `Tests/Integration` remain available for additional isolated and cross-layer suites when a project needs them.

The current DB-matrix harness verifies real schema creation, query execution, and repository round-trips for configured non-SQLite drivers. The runtime-backend harness verifies Redis cache/session and Memcached cache round-trips when the matching PHP extensions and services are available. The default framework suite carries the broader module, security, payment, presentation, and operational lifecycle coverage.

## Payment Drivers

LangelerMVC now treats payment providers as first-class framework drivers rather than module-local integrations.

- `testing`: reference/contract driver for regression testing and flow simulation
- `card`: generic credit/debit card adapter boundary
- `paypal`: PayPal wallet/card support through the framework payment manager
- `klarna`: Klarna BNPL-oriented driver
- `swish`: Swish support for Swedish local-instant checkout flows
- `qliro`: Qliro support for Swedish/Nordic checkout flows
- `walley`: Walley support for Nordic BNPL flows
- `crypto`: crypto invoice/reconciliation support for assets such as BTC/ETH

Each driver is exposed through the same framework surface:

- capability discovery
- supported payment methods and flows
- idempotent payment intent creation
- provider/external/webhook reference persistence
- redirect/customer-action metadata
- reconciliation hooks
- order/admin/health visibility

The framework core stays gateway-agnostic. Live credentials, callbacks, certificates, or merchant onboarding details belong in configuration and deployment, not in module code.

## Structure Docs

- [`Docs/README.md`](./Docs/README.md): documentation index and reading order.
- [`Docs/ArchitectureOverview.md`](./Docs/ArchitectureOverview.md): framework architecture, runtime flow, subsystem map, and extension points.
- [`Docs/FrameworkStatus.md`](./Docs/FrameworkStatus.md): current implementation status, remaining hardening areas, and environment-dependent verification notes.
- [`Docs/FolderStructure.md`](./Docs/FolderStructure.md): current architecture by layer and responsibility.
- [`Docs/ModulesStructure.md`](./Docs/ModulesStructure.md): module layout, conventions, and current module status.
- [`Docs/CompleteStructure.md`](./Docs/CompleteStructure.md): full current repository tree, excluding `.git` and `vendor`.
- [`Docs/DatabaseMatrixTesting.md`](./Docs/DatabaseMatrixTesting.md): how to run the MySQL/PostgreSQL/SQL Server plus Redis/Memcached verification harness locally.
- [`Docs/DeploymentAndUpgrade.md`](./Docs/DeploymentAndUpgrade.md): production deployment, upgrade, rollback, worker, and smoke-test recipes.
- [`Docs/InstallationWizard.md`](./Docs/InstallationWizard.md): first-run installer flow, configuration coverage, and post-install expectations.
- [`Docs/RepositoryMetadata.md`](./Docs/RepositoryMetadata.md): release-facing repository/package description, topics, about text, and publication posture.
- [`Docs/ThemeManagement.md`](./Docs/ThemeManagement.md): framework-wide Bootstrap-compatible light/dark/system theme configuration.
- [`Docs/OperationsGuide.md`](./Docs/OperationsGuide.md): health endpoints, audit logging, console operations, trusted-device behavior, and local backend verification.
- [`Docs/PaymentDrivers.md`](./Docs/PaymentDrivers.md): first-party payment-driver matrix, provider notes, and live-mode configuration expectations.
- [`Docs/ShippingAdapters.md`](./Docs/ShippingAdapters.md): first-party carrier adapter matrix, reference/live mode, and extension pattern.
- [`Docs/PresentationTemplating.md`](./Docs/PresentationTemplating.md): canonical `.vide` template authoring, supported directives, and rendering flow.
- [`Docs/PresentationLayerEvaluation.md`](./Docs/PresentationLayerEvaluation.md): presentation subsystem analysis, mature-framework comparison, differentiators, and next priorities.
- [`Docs/SanitationValidationAPI.md`](./Docs/SanitationValidationAPI.md): schema contract for sanitizers and validators.
- [`Docs/UtilitiesTraitsOverview.md`](./Docs/UtilitiesTraitsOverview.md): practical overview of the trait surface.
- [`Docs/UtilitiesTraitsReference.md`](./Docs/UtilitiesTraitsReference.md): generated trait reference.
- [`CONTRIBUTING.md`](./CONTRIBUTING.md): contributor workflow, verification expectations, and coding standards for framework changes.
- [`SECURITY.md`](./SECURITY.md): supported versions and responsible vulnerability disclosure guidance.

## Platform Status

LangelerMVC now ships as a complete first-party platform framework with:

- a thin bootstrap/runtime boundary
- provider-driven composition and lazy infrastructure
- validated session/auth/RBAC/TOTP/trusted-device/passkey support
- framework-native liveness/readiness/capability reporting and audit logging
- cache, crypto, SQL/query, migration, and seed subsystems
- async events, queues, notifications, and a plug-and-play payment compatibility layer with whole-catalog release checks
- plug-and-play payment driver support for PayPal, Klarna, Swish, Qliro, Walley, credit/debit cards, and crypto
- admin-native content, catalog, promotion, order, operation, inventory, return, and document workflows
- commerce coverage for physical shipping, digital/virtual access, pickup/pre-order, subscriptions, promotions, inventory reservations, returns/exchanges, partial refunds, and VAT/order documents
- an executable release gate through `composer release:check` and `composer verify:release`
- completed HTML + JSON presentation parity across first-party modules
- framework-wide Bootstrap-compatible light/dark/system theme management
- a database-backed starter module plus user/admin/shop/cart/order slices

The framework release is complete. Remaining work is intentionally deployment-specific rather than missing platform implementation: live DB-matrix execution in provisioned environments, Redis/Memcached-backed runtime verification where those services/extensions exist, project-specific payment/subscription/carrier credentials, full cross-browser visual/accessibility passes, and ongoing domain/policy refinement as real applications are built on top of the framework.

## Support

- Issues: [github.com/langeler/LangelerMVC/issues](https://github.com/langeler/LangelerMVC/issues)
- Source: [github.com/langeler/LangelerMVC](https://github.com/langeler/LangelerMVC)
- Wiki: [github.com/langeler/LangelerMVC/wiki](https://github.com/langeler/LangelerMVC/wiki)

## License

This project is licensed under the MIT License.
