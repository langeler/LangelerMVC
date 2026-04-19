# Modules Structure

This document describes the current module convention used by LangelerMVC and the present implementation status of each module.

## Module Convention

Every module inside `App/Modules` follows the same backend-oriented layout:

- `Controllers`: HTTP entrypoints that orchestrate request handling.
- `Middlewares`: module-specific middleware classes.
- `Migrations`: schema changes that belong to the module.
- `Models`: module domain models built on the framework persistence abstractions.
- `Presenters`: presentation transformers for templates or response payloads.
- `Repositories`: persistence adapters that isolate data access.
- `Requests`: typed request classes and input handling.
- `Responses`: module response classes for HTTP transport concerns.
- `Routes`: route definition files loaded by the router.
- `Seeds`: data seed classes for development and testing.
- `Services`: use-case logic and application services.
- `Views`: view classes that connect presenters to templates.

This structure is intentionally repeated across modules so the application layer stays predictable and easy to scale.

## How Modules Are Loaded

- `App\Utilities\Managers\Data\ModuleManager` discovers module directories under `App/Modules`.
- `App\Providers\ModuleProvider` registers module classes and aliases them for container resolution.
- `App\Core\Router` loads route files from module `Routes/` folders and dispatches controller actions from there.

Shared templates currently live in `App/Templates`, so modules can use a common presentation surface with shared layouts, partials, and components without duplicating markup primitives across modules.

## Current Module Status

| Module | Status | Notes |
| --- | --- | --- |
| `WebModule` | Implemented starter slice | Contains the reference request/controller/service/presenter/view/response pipeline plus `pages` migration, seed, model, repository, and shared templates. |
| `AdminModule` | Implemented management slice | Contains dashboard, user, role, system, catalog, cart, order, health, and operations management flows protected by the framework auth/RBAC layer. |
| `CartModule` | Implemented commerce slice | Contains guest/auth cart handling, merge-on-login listener, presenters/resources, routes, migrations, seeds, and views. |
| `OrderModule` | Implemented commerce slice | Contains checkout/order lifecycle services, listeners, notifications, presenters/resources, routes, migrations, seeds, and views. |
| `ShopModule` | Implemented commerce slice | Contains catalog services, presenters/resources, routes, migrations, seeds, views, and product/category persistence. |
| `UserModule` | Implemented identity slice | Contains registration, login, logout, password reset, email verification, RBAC, TOTP/recovery-code 2FA with trusted devices, and passkey/WebAuthn flows. |

## `WebModule` Today

`WebModule` is the first real application slice and currently shows how the framework is intended to be used:

- `Controllers/HomeController.php`
- `Requests/WebRequest.php`
- `Services/PageService.php`
- `Presenters/PagePresenter.php`
- `Views/WebView.php`
- `Responses/WebResponse.php`
- `Models/Page.php`
- `Repositories/PageRepository.php`
- `Routes/web.php`

It currently renders starter content through shared templates:

- `App/Templates/Layouts/WebShell.php`
- `App/Templates/Pages/Home.php`
- `App/Templates/Pages/NotFound.php`
- `App/Templates/Partials/PageIntro.php`
- `App/Templates/Components/BadgeList.php`

By default, `PageService` uses `Config/webmodule.php` with `CONTENT_SOURCE=database`. The seeded `pages` table is now the normal starter path, while memory-backed content remains an explicit fallback/testing mode.

## `UserModule` Today

`UserModule` is the first full platform/business slice and currently demonstrates:

- session-backed authentication
- password reset and email verification flows
- role/permission persistence and RBAC checks
- TOTP-based 2FA with recovery codes and trusted-device support
- passkey/WebAuthn registration and sign-in
- HTML + JSON endpoint parity through the same request/service/presenter/response pipeline

The module also contains framework-managed migrations and seeds for users, roles, permissions, auth tokens, and passkeys.

## `AdminModule` Today

`AdminModule` is the first protected management slice and currently demonstrates:

- admin dashboard metrics
- user and role/permission management flows
- framework inspection surfaces for modules, cache capabilities, routing, config, and sessions
- policy/permission-driven middleware for both HTML and JSON routes
- shared admin templates composed from reusable presentation partials and components

It now also exposes management visibility for:

- catalog data
- carts
- orders
- queue/notification/event/payment operational state
- framework health/readiness
- audit-backed diagnostics where safe

## `ShopModule` Today

`ShopModule` now provides the first catalog/business slice and demonstrates:

- product and category persistence
- module-managed migrations and seeds
- catalog listing/detail flows
- tracked public product artwork served from `Public/assets/images`
- publish-state and pricing handling
- HTML + JSON parity through presenters, resources, views, and responses

## `CartModule` Today

`CartModule` now demonstrates:

- guest and authenticated cart persistence
- session-backed cart identity
- merge-on-login behavior wired through framework auth events
- totals calculation in services
- HTML + JSON parity through the framework presentation pipeline

## `OrderModule` Today

`OrderModule` now demonstrates:

- checkout orchestration
- order, order-item, and address persistence
- payment-state lifecycle handling through the framework payment manager
- order lifecycle listeners and notifications
- HTML + JSON parity through presenters, resources, views, and responses

## Directory Notes

The `README.md` files that still exist inside module subfolders are intentional. They now document the purpose and current population of each repeated module directory, even when a given folder only needs a small number of concrete classes today.
