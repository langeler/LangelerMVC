# Folder Structure

This document describes the current LangelerMVC repository as it exists in code today. It is intentionally based on the tracked filesystem and implemented classes rather than on planned future layers.

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

Interfaces for the abstract and concrete framework surfaces. This layer now also includes console, session, presentation resource, and support-service contracts.

### `App/Console`

Operational CLI surface for the framework:

- `ConsoleKernel.php`: command registration and argument dispatch
- `Commands/`: first-party operational commands such as migrations, seeds, routes, cache, config, and module inspection

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
- `SeedRunner.php`: module-aware seed lifecycle runner.
- `Session.php`: framework session runtime.

### `App/Drivers`

Low-level adapters for pluggable infrastructure concerns:

- `Caching/`: concrete cache drivers (`ArrayCache`, `FileCache`, `DatabaseCache`, `RedisCache`, `MemCache`)
- `Cryptography/`: concrete crypto drivers
- `Session/`: concrete file, database, and redis session drivers

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

Reserved for helper classes that genuinely belong outside traits, managers, handlers, and modules. It is intentionally present but not used yet.

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

`WebModule`, `UserModule`, and `AdminModule` are implemented. `ShopModule`, `CartModule`, and `OrderModule` remain scaffolded with placeholder `README.md` files so the architecture remains visible in the repository tree.

### `App/Providers`

Container/provider wiring for core, cache, crypto, exception, and module registration.

### `App/Resources`

Reserved source assets that belong to the application layer rather than the public web root.

### `App/Templates`

Shared PHP templates used by concrete view classes. The current starter slice uses:

- `Layouts/WebShell.php`
- `Pages/Home.php`
- `Pages/NotFound.php`

`Components/` and `Partials/` are scaffolded for future shared view fragments.

### `App/Utilities`

Shared framework tooling. This is the main reusable backend toolbox and currently contains:

- `Finders/`: directory and file discovery
- `Handlers/`: focused data/system helper objects
- `Managers/`: concrete manager implementations and compatibility aliases
- `Managers/Support/`: framework-native mail, OTP, and passkey/WebAuthn service managers
- `Query/`: query builder helpers
- `Sanitation/`: sanitization implementations
- `Traits/`: reusable low-level behavior
- `Validation/`: validator implementations

Important note:

- `App/Utilities/Managers/System/*` and `App/Utilities/Managers/Data/*` contain concrete implementations for several services.
- Some flat manager classes in `App/Utilities/Managers/*` exist as convenience or compatibility wrappers over those deeper implementations.

## Other Top-Level Folders

### `Config`

Tracked runtime configuration arrays. The framework loads these through `SettingsManager` and `Config`, then merges environment overrides from `.env` at runtime.

Notable current files include:

- `auth.php`
- `mail.php`
- `session.php`
- `webmodule.php`

### `Data`

Standalone SQL reference files. They remain in the repository as reference material, but the framework now has its own migration and seed lifecycle under `App/Core`, `App/Abstracts/Database`, and module `Migrations/` / `Seeds/`.

### `Docs`

Current documentation plus historical PDF/RTF reference material.

Primary current docs:

- `README.md`
- `ArchitectureOverview.md`
- `FrameworkStatus.md`
- `FolderStructure.md`
- `ModulesStructure.md`
- `CompleteStructure.md`
- `SanitationValidationAPI.md`

### `Public`

The web-facing document root:

- `index.php` is the thin front controller.
- `.htaccess` contains the Apache rewrite and baseline public protections.
- `assets/` contains public asset directories.

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
- `Unit/`: scaffolded for isolated class tests
- `Integration/`: scaffolded for cross-layer tests

### `console`

The first-party framework CLI entrypoint. It boots `bootstrap/console.php` and dispatches the command kernel.

### `autoload.php`

Legacy fallback autoload helper. It is still tracked, but the primary application bootstrap uses Composer through `bootstrap/app.php`.

## Notes

- Placeholder `README.md` files were intentionally added to previously empty folders so the repository can communicate the full planned architecture without invisible directories.
- The canonical current documentation entrypoints are `Docs/README.md`, `Docs/ArchitectureOverview.md`, and `Docs/FrameworkStatus.md`.
