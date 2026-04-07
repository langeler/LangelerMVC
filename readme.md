![Logo](./logo.jpeg)

# LangelerMVC

[![PHP Version](https://img.shields.io/badge/PHP-8.4%2B-blue.svg)](https://www.php.net/)

LangelerMVC is a custom-built PHP MVC framework designed with a strong focus on structure, modularity, and best practices, including SRP, SoC, and OOP principles.

## Current State

- The framework bootstrap, runtime, dependency container, configuration layer, routing, session handling, validation, sanitization, HTTP/MVC abstractions, and persistence foundations are implemented and covered by the current regression suite.
- `WebModule` is the first completed vertical slice and currently serves starter page content from memory by default, with repository-backed loading already wired for a future `pages` schema.
- `AdminModule`, `CartModule`, `OrderModule`, `ShopModule`, and `UserModule` are scaffolded to show the intended architecture, but they are not implemented yet.
- Intentionally empty architecture folders now contain lightweight `README.md` placeholders so the repository tree shows the complete planned structure.

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
- `Contracts/`: interface surface for the abstractions and core layer.
- `Core/`: framework runtime services such as `App`, `Bootstrap`, `Config`, `Container`, `Database`, `Router`, and `Session`.
- `Drivers/`: low-level pluggable adapters. Caching and cryptography are present; session drivers are scaffolded.
- `Exceptions/`: typed framework exception classes grouped by concern.
- `Modules/`: application modules. `WebModule` is implemented; the other modules are scaffolded.
- `Providers/`: container/provider wiring for core, cache, crypto, exceptions, and modules.
- `Resources/`: source asset placeholders that belong to the application layer.
- `Templates/`: shared PHP template files used by module views.
- `Utilities/`: shared traits, handlers, managers, finders, query helpers, validators, and sanitizers.

### Other Root Folders

- `Config/`: runtime configuration arrays loaded by the config facade and merged with `.env` overrides at runtime.
- `Data/`: standalone SQL reference files. These are not yet wired into a migration runner.
- `Docs/`: current architecture and structure docs, plus older reference materials kept in the repository.
- `Public/`: the public document root, front controller, Apache config, and public asset folders.
- `Services/`: reserved for cross-application service composition outside a specific module.
- `Storage/`: cache, logs, secure keys, sessions, and uploads.
- `Tests/`: framework regression coverage plus scaffolded `Unit` and `Integration` suites.
- `autoload.php`: legacy fallback autoload helper. The primary bootstrap path uses Composer through `bootstrap/app.php`.

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

## Configuration Notes

- `.env` provides environment-specific overrides.
- `Config/*.php` files provide the tracked runtime configuration surface.
- `Config/webmodule.php` controls the current `WebModule` content source.
  - `CONTENT_SOURCE=memory` keeps the starter module self-contained.
  - `CONTENT_SOURCE=database` enables repository-backed loading once the corresponding schema exists.
- Session files are stored in `Storage/Sessions` by default.

## Testing

Run the current regression suite with:

```bash
composer test
```

The active framework tests live in `Tests/Framework`. `Tests/Unit` and `Tests/Integration` are intentionally present as scaffolded suites for future growth.

## Structure Docs

- [`Docs/FolderStructure.md`](./Docs/FolderStructure.md): current architecture by layer and responsibility.
- [`Docs/ModulesStructure.md`](./Docs/ModulesStructure.md): module layout, conventions, and current module status.
- [`Docs/CompleteStructure.md`](./Docs/CompleteStructure.md): full current repository tree, excluding `.git` and `vendor`.

## Development Status

LangelerMVC now has a stable core foundation and a real starter module. The next major implementation work is at the application layer: building concrete modules such as `UserModule`, `ShopModule`, `CartModule`, `OrderModule`, and `AdminModule` on top of the framework surfaces that now exist.

## Support

- Issues: [github.com/langeler/LangelerMVC/issues](https://github.com/langeler/LangelerMVC/issues)
- Source: [github.com/langeler/LangelerMVC](https://github.com/langeler/LangelerMVC)
- Wiki: [github.com/langeler/LangelerMVC/wiki](https://github.com/langeler/LangelerMVC/wiki)

## License

This project is licensed under the MIT License.
