# Migrations

This directory is part of the implemented `AdminModule` module contract. Database migrations owned by the module schema lifecycle.

`AdminModule` is intentionally orchestration-first and does not currently own standalone schema tables.

Its dashboards and management flows operate on framework-level state plus the persistence layers owned by `UserModule`, `ShopModule`, `CartModule`, `OrderModule`, notifications, queues, and audit storage.
