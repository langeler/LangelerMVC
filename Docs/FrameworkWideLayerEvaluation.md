# Framework-Wide Layer Evaluation

This document broadens the release-readiness view from individual modules into a framework-wide, layer-by-layer production audit.

It is backed by the executable `framework:layers` console command and the `framework_layers` section in `composer release:check`.

## Current Position

LangelerMVC is now best described as a compact, integrated PHP 8.4 MVC platform rather than a minimal MVC skeleton.

The framework-wide layer map is intentionally explicit:

- Public / Bootstrap
- Core Runtime
- Providers / Container
- Contracts / Abstracts
- HTTP / MVC
- Presentation / View / Theme / Assets
- Data / Persistence
- Security / Auth
- Drivers
- Utilities / Managers
- Modules
- Installer
- Console / Operations
- Release / Docs / Data

`App\Utilities\Managers\Support\FrameworkLayerManager` owns that map and reports required path coverage. This keeps the project goal of "organized and structured" enforceable instead of merely documented.

## Layer Evaluation

| Layer | Current release posture | Main responsibility |
| --- | --- | --- |
| Public / Bootstrap | Complete | Thin web/console entrypoints hand off to bootstrap/runtime. |
| Core Runtime | Complete | App lifecycle, config, container, database, router, sessions, migrations, seeds. |
| Providers / Container | Complete | Service aliases, lazy resolution, provider-backed infrastructure seams. |
| Contracts / Abstracts | Complete | Typed extension seams for framework and application code. |
| HTTP / MVC | Complete | Request, response, controller, middleware, service, routing, negotiation. |
| Presentation / View / Theme / Assets | Complete with ongoing ergonomics | Native `.vide`, sections/stacks, shared layouts, safe HTML, themes, assets. |
| Data / Persistence | Complete | Models, repositories, queries, schema, migrations, seeds, SQL snapshots. |
| Security / Auth | Complete | Auth, RBAC, OTP, passkeys, signed URLs, throttling, sessions. |
| Drivers | Complete reference catalog | Cache, crypto, notifications, payments, passkeys, queues, sessions, shipping. |
| Utilities / Managers | Complete canonical toolbox | Shared traits, managers, finders, handlers, validation, sanitation, query helpers. |
| Modules | Complete first-party baseline | Web, User, Admin, Shop, Cart, and Order modules exercise the framework. |
| Installer | Complete first-run path | Guided setup for env, database, admin, modules, integrations, theme, operations. |
| Console / Operations | Complete | Health, readiness, audit, queues, events, notifications, routes, release gates. |
| Release / Docs / Data | Complete | Release metadata, docs, wiki source, data snapshots, executable checks. |

## Mature Framework Comparison

The comparison below is intentionally grounded in official documentation:

- Laravel: [Blade](https://laravel.com/docs/13.x/blade) and [Vite](https://laravel.com/docs/13.x/vite)
- Symfony: [Templates](https://symfony.com/doc/current/templates.html), [UX Twig Components](https://symfony.com/doc/current/ux-twig-component/index.html), and [AssetMapper](https://symfony.com/doc/current/frontend/asset_mapper.html)
- Yii: [Asset Bundles](https://www.yiiframework.com/doc/guide/2.0/en/structure-assets)
- CodeIgniter: [View Layouts](https://codeigniter.com/user_guide/outgoing/view_layouts.html)
- CakePHP: [Views](https://book.cakephp.org/5.x/views.html) and [Helpers](https://book.cakephp.org/5.x/views/helpers.html)
- Laminas: [View quick start](https://docs.laminas.dev/laminas-view/v3/quick-start/) and [view helpers](https://docs.laminas.dev/laminas-view/v3/helpers/intro/)

| Capability | Mature-framework expectation | LangelerMVC release posture |
| --- | --- | --- |
| Template compilation | Cached template rendering | Native `.vide` and `.lmv` compile to cached PHP. |
| Layout composition | Sections, yields, stacks, or view models | `.vide` now supports sections/stacks plus legacy `$content` flow. |
| Components | Includeable partials/components; class-backed components in larger ecosystems | Template components exist; typed component classes remain a future ergonomic layer. |
| Escaping and HTML helpers | Escaped output, attributes, classes, CSRF, method helpers | `HtmlManager` and `.vide` directives centralize safe output. |
| Assets | Versioned assets, bundles, or build-tool integration | `AssetManager` supports versioned URLs, preloads, named bundles, and sync checks. |
| Frontend build integration | Vite/AssetMapper-style manifests in larger ecosystems | First-party assets are dependency-light; manifest/build-tool integration is a future optional layer. |
| Resource/JSON presentation | API resources or JSON view models | Resource and resource-collection abstractions support negotiated JSON. |
| Operational tooling | Console, health, queue, audit, diagnostics | First-party console, health/readiness, audit, queue, release, and layer checks exist. |
| Package/module organization | Packages, bundles, modules, components | First-party modules and provider/driver seams are complete; package ecosystem is intentionally young. |

## What Makes LangelerMVC Distinct

LangelerMVC's strongest differentiator is not raw ecosystem size. It is integration density.

- The framework ships a coherent auth/admin/commerce/operator baseline instead of requiring many unrelated packages to create a production-shaped application.
- `.vide` is framework-native and directive-first, encouraging clear separation between presentation intent and PHP runtime logic.
- Release readiness is executable through `release:check`, not just documented in prose.
- Swedish carrier awareness, Mina Paket handoff metadata, subscriptions, promotions, digital entitlements, pickup/pre-order, VAT/order documents, and admin operations all exist in the first-party baseline.
- Canonical managers under `App\Utilities\Managers` make cross-layer behavior discoverable instead of scattering framework utilities across support folders.

## Remaining Advanced Gaps

These are not framework release blockers, but they are the clearest areas where large mature ecosystems still go further:

- A typed component class API with props and slots on top of `.vide` templates.
- Optional build-tool manifest support for Vite, esbuild, Rollup, or Symfony AssetMapper-style logical assets.
- Per-module template namespaces and override paths for package-style theme customization.
- Fragment caching directives for expensive partials/components.
- Template diagnostics with source-line mapping for compile/runtime errors.
- Browser and accessibility regression automation beyond static heuristics and local smoke checks.
- Larger third-party package ecosystem and formal extension marketplace conventions.

## Implementation Guidance

Future framework changes should follow this order:

1. Keep `framework:layers` and `release:check` green before expanding features.
2. Add new shared orchestration classes under focused `App\Utilities\Managers/*` sublayers.
3. Add contracts when a subsystem is expected to be provider-, driver-, or project-replaceable.
4. Prefer `.vide` directives and `HtmlManager` helpers over raw PHP in templates.
5. Keep live credentials, merchant secrets, carrier credentials, and seller legal identity deployment-local.
