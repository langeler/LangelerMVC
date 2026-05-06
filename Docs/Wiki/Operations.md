# Operations

LangelerMVC includes first-party operational surfaces rather than leaving production visibility entirely to application code.

## Console

```bash
php console list
php console health:check
php console health:check ready
php console framework:doctor
php console framework:doctor --strict
php console framework:architecture
php console framework:layers
php console audit:list --limit=25
php console migrate
php console seed WebModule
php console route:list
php console queue:work
php console queue:failed
php console release:check
```

## Runtime Health

- `GET /health`: liveness
- `GET /ready`: readiness

Readiness covers database, cache, session, queue, notifications, payment, mail, passkeys, audit, and framework structure.

## Admin Operations

The admin module exposes protected operator panels for dashboard, users, roles, pages, catalog, promotions, carts, orders, operations, system, health, inventory, returns, documents, audit drilldowns, queues, notifications, events, payments, and carrier-adapter visibility.

## Deployment Notes

- Keep `.env` and all secrets deployment-local.
- Keep `Storage/Cache`, `Storage/Logs`, `Storage/Secure`, `Storage/Sessions`, `Storage/Uploads`, and queue runtime paths writable.
- Run migrations before opening traffic.
- Start supervised queue workers for async queues.
- Use `php console framework:layers` for layer organization checks, `php console framework:architecture` for class placement, strict class files, canonical manager placement, support alias corridors, module shape, native presentation, and docs alignment, `composer release:check` for framework release posture, and `php console release:check --strict=1` for project go-live posture.
