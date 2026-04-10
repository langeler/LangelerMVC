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

Shared templates currently live in `App/Templates`, so modules can use a common presentation surface without duplicating layout files.

Important current limitation:

- Module `Migrations/` and `Seeds/` folders are present by convention, but the framework does not yet provide a migration runner or seed runner.

## Current Module Status

| Module | Status | Notes |
| --- | --- | --- |
| `WebModule` | Implemented starter slice | Contains a working controller, request, service, presenter, response, view, route file, model, and repository. |
| `AdminModule` | Scaffolded | Folder structure is present with placeholder `README.md` files only. |
| `CartModule` | Scaffolded | Folder structure is present with placeholder `README.md` files only. |
| `OrderModule` | Scaffolded | Folder structure is present with placeholder `README.md` files only. |
| `ShopModule` | Scaffolded | Folder structure is present with placeholder `README.md` files only. |
| `UserModule` | Scaffolded | Folder structure is present with placeholder `README.md` files only. |

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

By default, `PageService` uses `Config/webmodule.php` with `CONTENT_SOURCE=memory`. The service is already prepared to use the repository path once a concrete `pages` schema is added.

## Placeholder Files

The placeholder `README.md` files in scaffolded module folders are intentional. They make the full intended module architecture visible in the repository tree and give each folder an explicit purpose before implementation begins.

## Recommended Module Build Order

From the current framework state, the strongest next module order is:

1. `UserModule`
2. `ShopModule`
3. `CartModule`
4. `OrderModule`
5. `AdminModule`

That order lets the application layer grow on top of the framework in dependency order instead of implementing admin or order management before user and catalog foundations exist.
