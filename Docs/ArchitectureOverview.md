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
- `App\Core\App` boots core services, applies runtime policy, dispatches the request, and emits the response.

### Core Runtime

`App/Core` contains the framework runtime services:

- `App`: application lifecycle and dispatch orchestration
- `Bootstrap`: runtime preparation and application creation
- `Config`: runtime-facing config access
- `Container`: reflection-driven dependency resolution
- `Database`: lazy PDO wrapper plus query factories
- `MigrationRunner`: module-aware schema lifecycle runner
- `Router`: route registration, caching, dispatch, and fallback handling
- `SeedRunner`: module-aware data seed lifecycle runner
- `Session`: framework session runtime aligned with the config layer
- `ModuleManager`: compatibility alias over the concrete module manager

### Providers

`App/Providers` translates framework services into container-managed entrypoints:

- `CoreProvider`: core service registration and application creation
- `ModuleProvider`: module discovery and registration
- `ExceptionProvider`: typed exception alias resolution
- `CacheProvider`: cache driver resolution
- `CryptoProvider`: crypto driver resolution

Providers are the framework’s infrastructure composition boundary. They let the runtime depend on contracts and aliases instead of hardcoding driver classes directly.

### Contracts And Abstracts

`App/Contracts` and `App/Abstracts` define the main extension seams.

These cover:

- data helpers such as cache, finder, sanitizer, validator, and crypto
- persistence abstractions such as model, repository, migration, seed, and query
- HTTP abstractions such as controller, request, response, middleware, and service
- presentation abstractions such as presenter and view

These layers are intentionally reusable. Application code is expected to extend them rather than bypass them.

### Drivers

`App/Drivers` contains low-level backend adapters:

- `Caching`: `ArrayCache`, `FileCache`, `DatabaseCache`, `RedisCache`, `MemCache`
- `Cryptography`: `OpenSSLCrypto`, `SodiumCrypto`
- `Session`: `FileSessionDriver`, `DatabaseSessionDriver`, `RedisSessionDriver`

The driver layer exists so the framework can present a stable API to the rest of the application while still allowing backend implementation changes.

### Utilities

`App/Utilities` is the shared toolkit layer used by the framework itself:

- `Finders`: file and directory discovery
- `Handlers`: focused utility objects
- `Managers`: concrete system/data services plus compatibility aliases
- `Managers/Support`: framework mail, OTP, and passkey/WebAuthn service managers
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
- migration and seed lifecycle management
- session runtime
- file, database, and redis session driver adapters
- session-backed authentication, RBAC, TOTP/recovery-code 2FA, and passkey/WebAuthn support
- sanitation and validation APIs
- HTTP/MVC abstraction layer
- presentation resource / negotiated JSON layer
- view/presenter layer
- database layer and SQL/query builders
- cache subsystem
- crypto subsystem
- mail, OTP, and passkey/WebAuthn service boundaries
- file, finder, iterator, and reflection utilities
- model and repository foundations

These subsystems are also covered by the current regression suite under `Tests/Framework`.

## Concrete Application Surface Today

The application layer is intentionally not “finished everywhere” yet.

Current concrete state:

- `WebModule` is the first real starter/content slice.
- `UserModule` is the first full identity/platform slice.
- `AdminModule` is the first protected management slice.
- `WebModule` has a controller, request, service, presenter, view, response, model, repository, route file, migration, and seed.
- `UserModule` now provides registration, login, logout, password reset, email verification, RBAC, TOTP/recovery-code 2FA, and passkey/WebAuthn flows.
- `AdminModule` now provides dashboard, user, role/permission, and framework-inspection flows.
- Shared templates currently live in `App/Templates/Layouts` and `App/Templates/Pages`.
- `ShopModule`, `CartModule`, and `OrderModule` are scaffolded only.

This means the framework backend is ahead of the business/domain implementation, which is the expected current shape of the project.

## Extension Points

The most important extension seams today are:

- **Modules**: add real business slices under `App/Modules/*`.
- **Persistence**: add migrations, seeds, repositories, and real schemas on top of the existing database/query/model base.
- **Console**: add more operational commands and later scaffolding/generator support.
- **Views**: expand templates, layouts, components, and partials.
- **Drivers**: add more concrete infrastructure backends through providers and contracts.
- **Validation / Sanitization**: extend schema methods and rules through the existing APIs.
- **Caching / Crypto**: change backends without changing higher-level application code.
- **Support Services**: extend authentication, authorization, notifications, and workflow features on top of the mail/OTP/passkey/session boundaries.

## Current Architectural Limits

The framework is strong at the backend foundation level, but a few architecture areas are still intentionally incomplete:

- no real commerce/business modules beyond `WebModule`, `UserModule`, and `AdminModule`
- no framework-native notification or queue subsystem yet
- no event dispatcher/listener system yet
- no concrete shop, cart, or order domains yet

Those are the main places where future framework work should build on the current base rather than rework it.
