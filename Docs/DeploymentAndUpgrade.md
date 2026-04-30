# Deployment And Upgrade

This document turns the release checklist into practical operator recipes. It is intentionally provider-neutral so projects can use the same framework path on local VPS, managed PHP hosting, containers, or CI/CD platforms.

## Production Deployment Recipe

1. Provision PHP `8.4+`, Composer, the target SQL server, cache/session backend, queue worker runtime, mail transport, and web server.
2. Clone or unpack the release artifact and run `composer install --no-dev --classmap-authoritative`.
3. Point the web server document root at `Public/` and enable front-controller rewrites.
4. Run the installer at `Public/install/index.php` for first boot, or review an existing deployment-local `.env` against `.env.example`.
5. Store secrets, webhook signing keys, payment credentials, carrier credentials, mail credentials, and app keys outside version control.
6. Ensure `Storage/Cache`, `Storage/Logs`, `Storage/Secure`, `Storage/Sessions`, `Storage/Uploads`, and queue runtime paths are writable by the PHP process.
7. Run `php console migrate` and `php console seed` when the environment needs starter data.
8. Start supervised queue workers for the configured queues, then verify `php console queue:failed` is empty after initial smoke tests.
9. Run `php console health:check`, `php console health:check ready`, `composer ops:health`, and `composer ops:ready` against the target environment.
10. Run a browser smoke pass for public storefront pages, installer redirect behavior, admin dashboard, admin orders, admin promotions, admin operations, and auth flows.

## Commerce Deployment Checks

- Confirm payment provider credentials, webhook URLs, webhook secrets, return URLs, and callback/firewall rules before enabling live mode.
- Confirm subscription provider ownership: provider-managed recurring billing should emit subscription webhooks; framework-managed renewal schedules should be backed by queue/cron execution.
- Confirm carrier mode and credentials for PostNord, InstaBox, BudBee, Bring, DHL, Schenker, Early Bird, Airmee, UPS, and any Mina Paket tracking handoff behavior that the project enables.
- Confirm shipping is not charged for digital, virtual, subscription-only, or other non-shipped fulfillment lines.
- Confirm pickup, scheduled pickup, and pre-order policies match the store promise before opening checkout.
- Confirm `COMMERCE_INVENTORY_RESERVE_ON_CHECKOUT`, `COMMERCE_INVENTORY_RELEASE_ON_CANCEL`, and `COMMERCE_INVENTORY_RESERVATION_TTL_MINUTES` match the stock-hold policy.
- Confirm `COMMERCE_RETURNS_WINDOW_DAYS`, `COMMERCE_RETURNS_ALLOW_EXCHANGES`, and `COMMERCE_RETURNS_AUTO_RESTOCK` match the return policy.
- Confirm `COMMERCE_DOCUMENTS_VAT_RATE_BPS`, `COMMERCE_DOCUMENTS_SELLER_NAME`, `COMMERCE_DOCUMENTS_SELLER_VAT_ID`, and `COMMERCE_DOCUMENTS_SELLER_ADDRESS` match the seller/legal entity.

## Upgrade Recipe

1. Read `CHANGELOG.md`, `RELEASE.md`, and `Docs/ReleaseReadinessPlan.md` for the target version.
2. Back up the database, deployment-local `.env`, and secret material.
3. Put the application in maintenance mode at the hosting/router layer if the deployment platform supports it.
4. Deploy the new code artifact and run `composer install --no-dev --classmap-authoritative`.
5. Run `php console migrate`.
6. Re-run `php console health:check ready` and the environment's smoke test subset before switching traffic.
7. Check admin operations for queue, notification, event, payment, health, inventory, return/document, and audit panels.
8. Observe payment webhooks, subscription events, queue workers, and carrier callbacks after traffic returns.
9. Keep the previous code artifact and database backup available until the post-deploy observation window is complete.

## Rollback Recipe

1. Stop or pause traffic at the load balancer/router if the failure is user-facing.
2. Stop queue workers to prevent additional side effects while the rollback decision is made.
3. Restore the previous code artifact.
4. Restore the database backup if the failed release ran non-reversible migrations or wrote incompatible data.
5. Restore the previous `.env` or secret set if configuration changed.
6. Restart web and worker processes.
7. Run health/readiness checks and inspect admin operations, audit, failed queues, and payment/subscription webhook ledgers.

## Release Smoke Matrix

- Public: home page, catalog listing, product detail, cart, checkout, payment return/cancel, digital entitlement access, and order confirmation.
- Identity: registration, login, logout, password reset, email verification, TOTP, passkeys, and profile/trusted-device visibility.
- Admin: dashboard, pages, catalog, promotions, orders, operations, users, roles, system, and permissions.
- Commerce: physical shipping, digital/virtual fulfillment, pickup/scheduled pickup, pre-order, subscription checkout, promotions/coupons, inventory reservations, returns/exchanges, partial refunds, and order documents.
- Operations: health, readiness, queue work/retry/failure paths, notifications, audit list, payment webhooks, subscription webhooks, and carrier tracking sync.
- Accessibility/browser: keyboard navigation, labels, focus states, responsive layout, and current Chrome/Safari/Firefox smoke coverage.
