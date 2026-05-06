# Architecture Alignment

This document is the human-readable companion to the executable architecture-alignment gate:

```bash
php console framework:architecture
composer architecture:check
```

The check is implemented by `App\Utilities\Managers\Support\ArchitectureAlignmentManager`, exposed through `App\Contracts\Support\ArchitectureAlignmentManagerInterface`, registered as the core `architecture` service alias, and included in `php console release:check` as the `architecture_alignment` payload section.

## Purpose

LangelerMVC has a strong project goal around being organized, structured, OOP-focused, SRP-oriented, and friendly to plug-and-play production use. The framework-wide layer check confirms that major folders exist. The architecture-alignment check goes one level deeper and verifies that the repository keeps following the conventions that make those layers useful.

The goal is not bureaucracy. The goal is that future framework growth remains predictable, easy to inspect, and safe to extend.

## Enforced Rules

### Repository Contract

The root repository shape is part of the framework contract, not decoration. The architecture gate verifies release-critical root files and directories such as `App`, `Config`, `Data`, `Docs`, `Public`, `Scripts`, `Tests`, `.env.example`, `.github/workflows/php.yml`, `composer.json`, `docker-compose.verify.yml`, PHPUnit configs, and release/security docs.

It also verifies runtime ignore policy. Deployment-local files such as `.env`, `vendor`, and generated storage/cache material must remain outside release tracking while sentinel `README.md` files keep runtime directories visible.

### App Layer Boundaries

The `App/` tree must keep the expected top-level layer map:

- `Abstracts`
- `Console`
- `Contracts`
- `Core`
- `Drivers`
- `Exceptions`
- `Framework`
- `Installer`
- `Modules`
- `Providers`
- `Resources`
- `Support`
- `Templates`
- `Utilities`

Direct PHP files do not belong in the `App/` root. New framework code should land in the narrowest correct layer rather than creating ad hoc top-level folders.

### Public / Bootstrap

`Public/index.php`, `bootstrap/app.php`, `bootstrap/console.php`, and `console` must stay thin entrypoints. They should delegate into `App\Core\Bootstrap`, the application runtime, or the console kernel rather than becoming business-logic surfaces.

`Public/install/index.php` is intentionally a slightly richer public entrypoint because it owns the first-run installer bridge. It must keep integrating `InstallerWizard`, `InstallerView`, and `HttpSecurityManager` instead of bypassing the framework installer and security layers.

### Config / Data / Release Parity

Tracked config files, `.env.example`, grouped `Data/*.sql` snapshots, and `Data/README.md` must stay aligned. Migrations remain authoritative, but the release SQL snapshots are part of onboarding, review, and release auditability.

### Tests / CI / Scripts

The framework must keep its verification surface intact:

- default PHPUnit framework suite
- optional DB/runtime matrix suite
- GitHub Actions default and supported matrix jobs
- local `docker-compose.verify.yml` services for MySQL, PostgreSQL, SQL Server, Redis, and Memcached
- Composer scripts for tests, health, architecture, release, and platform verification
- maintenance scripts for native-to-trait and trait-reference audits

### Strict Class Files

Class-bearing `App/` PHP files must declare `strict_types=1`.

This reinforces the PHP 8.4 posture of the framework and keeps scalar type behavior predictable across core runtime, managers, drivers, modules, and support classes. Template compatibility files and route/config return files are not treated as class-bearing surfaces by this rule.

### Canonical Managers

Concrete manager implementations belong under `App/Utilities/Managers/*`.

Current canonical sublayers include:

- `App/Utilities/Managers/Async`
- `App/Utilities/Managers/Commerce`
- `App/Utilities/Managers/Data`
- `App/Utilities/Managers/Presentation`
- `App/Utilities/Managers/Security`
- `App/Utilities/Managers/Support`
- `App/Utilities/Managers/System`

Legacy paths such as `App/Support/Commerce/*Manager.php`, `App/Support/Commerce/CommerceTotalsCalculator.php`, `App/Support/Theming/ThemeManager.php`, and `App/Core/ModuleManager.php` remain supported only as thin compatibility aliases. New framework code should depend on the canonical manager namespaces.

### Documented Module Shape

First-party modules must keep the repeated module contract:

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

Each module subdirectory also needs a local `README.md`. This makes intentionally empty surfaces clear instead of making developers guess whether something is missing.

### Native Presentation Surface

The presentation layer must keep the native `.vide` surface aligned:

- `ViewInterface` exposes section/stack composition methods.
- `TemplateEngine` supports `@section`, `@endsection`, `@yield`, `@push`, `@endpush`, `@stack`, and `@hasSection`.
- Shared layouts, pages, partials, components, source assets, and public assets remain present.
- First-party `.vide` templates do not embed raw PHP tags.

This keeps `.vide` feeling like a framework-native templating language rather than a thin include wrapper.

### Documentation Alignment

The architecture docs must name the executable checks and the canonical boundaries they protect. Historical PDF/RTF files are allowed to remain tracked for context, but they must be listed as archival material rather than treated as current source-of-truth documentation.

## Release Behavior

Normal release checks fail if architecture-alignment errors exist:

```bash
php console release:check
```

Strict release checks still include the same architecture section, then add deployment-local warnings such as live credentials, optional PHP extensions, matrix services, and browser/accessibility passes:

```bash
php console release:check --strict=1
```

## When To Update The Rules

Update `ArchitectureAlignmentManager` and this document when a change intentionally modifies one of the framework's organizing contracts.

Good reasons include:

- introducing a new required module subdirectory
- adding a new top-level release contract path
- adding a new canonical manager sublayer
- changing public/bootstrap entrypoint responsibilities
- adding or removing config, SQL snapshot, CI, test, or maintenance-script surfaces
- moving a compatibility alias to a new canonical namespace
- adding a new required `.vide` composition primitive
- changing documentation source-of-truth expectations

Avoid loosening the rules just to make an isolated change pass. If a rule catches friction, either move the code into the right layer or update the architecture intentionally with tests and docs.
