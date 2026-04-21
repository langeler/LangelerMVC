# Middlewares

This directory is part of the implemented `ShopModule` module contract. Module-specific middleware that runs before or around controller execution.

`ShopModule` currently keeps storefront filtering, pagination, publish-state handling, and 404 behavior inside `ShopRequest` and `CatalogService`.

That is intentional: the catalog needs to return first-class storefront pages and API resources rather than short-circuiting route handling too early in middleware.
