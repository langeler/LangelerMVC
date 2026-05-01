# Release Readiness Plan

This plan tracks the remaining work to move LangelerMVC from late-stage framework completion to a production-ready, plug-and-play release.

## Current Release Position

The framework foundation is implemented: core runtime services, first-party modules, native `.vide` templates, framework-wide theme management, admin workflows, WebModule page authoring, commerce totals, payment driver contracts, signed/idempotent payment and subscription webhooks, Swedish carrier-aware shipping adapters with reference booking/label/tracking seams, digital/virtual entitlements, DB-backed subscription runtime, promotions with usage ledgers and analytics, inventory reservation ledgers, return/exchange workflows, partial refunds, VAT/order documents, health checks, audit tooling, queues, runtime backend harnesses, and installer rollback are all present.

The remaining work is now the final production layer: live integration credentials, environment matrix verification, deployment recipe validation, and browser/accessibility smoke passes.

## P0 - Release Blockers

- Remove tracked runtime/security material from version control and ensure secrets are installer/runtime generated. The tracked secure cache key has been removed, and `Storage/Secure` stays ignored except for its README.
- Update stale release documentation, status files, setup references, and test counts before tagging a release. `CHANGELOG.md`, `RELEASE.md`, and `Docs/DeploymentAndUpgrade.md` now act as release-facing anchors.
- Bring `.env.example`, installer defaults, and SettingsManager aliases into parity with the current framework surface. Current coverage includes queue workers, notifications, HTTP/auth, operations, theme management, payment provider endpoints, commerce inventory, returns, documents, and list-style env handling.
- Keep `Data/*.sql` synchronized as release-reference schema snapshots generated from migrations; migrations remain authoritative, and local `.env` files remain deployment-specific and ignored.
- Payment webhook environment parity is now included: installer defaults, `.env.example`, SettingsManager aliases, route integration, signature settings, and per-driver secrets are represented.
- Keep the installer as the authoritative first-run path for database, modules, admin account, payments, commerce, fulfillment, queues, mail, auth, and operations.
- Run `composer release:check` as the local release gate and `php console release:check --strict=1` when validating a production tag candidate with live credentials and matrix extensions available.
- Verify the full database/cache/session matrix before release, not only the default local regression suite. `composer test:db-matrix` and `composer test:runtime-backends` are the documented opt-in harnesses.

## P1 - Commerce Fulfillment Spectrum

Commerce must model fulfillment strategies, not assume every order is shipped.

- Physical shipped products use carrier shipping, tracking, and shipment lifecycle states.
- Digital downloads skip shipping and now grant order-scoped download entitlements with access keys, limits, windows, and admin revoke/reactivate controls.
- Virtual or online access purchases skip shipping and use the same entitlement foundation for gated content/access URLs.
- Store pickup and scheduled pickup use pickup fulfillment options rather than carrier delivery.
- Pre-orders need availability dates, customer messaging, and release workflows.
- Subscriptions now have plans, recurring payment schedules, renewal orders, payment retry/dunning, pause/resume/cancel, entitlement syncing, admin operations, and webhook-driven reconciliation in the framework reference runtime.
- Mixed carts must support physical plus digital/virtual/subscription products while only charging shipping for the physical fulfillment portion.

## P1 - Promotion And Coupon Breadth

Promotions should be treated as rules plus benefits.

- Benefit types should include percentage, fixed amount, currency-specific exact amount, free shipping, fixed shipping rate, and shipping percentage discounts.
- Criteria should include currency, subtotal ranges, item counts, product IDs, product slugs, category IDs, fulfillment types, shipping countries, zones, carriers, shipping options, active windows, and excluded products/types.
- Promotions now have database-managed admin records with audit events, activation windows, global/per-customer/per-segment usage limits, customer/account/segment criteria, runtime catalog integration, checkout usage ledgers, bulk admin lifecycle workflows, and analytics by code, source, currency, customer, segment, and day.

## P1 - Admin Operator Completion

- Convert raw system/operations pages into structured operator panels. Current implementation includes queue, notification, event, payment, health, inventory, return/document, and audit drilldown panels.
- Add admin-native promotion/coupon management. Current implementation includes protected admin routes, controller actions, service workflows, resource payloads, native `.vide` templates, checkout usage reporting, promotion analytics, bulk workflows, and confirmation UX.
- Add WebModule page authoring and publishing. Current implementation includes admin-native create/update/publish/unpublish/delete flows, protected home-page deletion guardrails, resource payloads, route parity, and native `.vide` templates.
- Add richer filters, bulk actions, audit drilldowns, and lifecycle confirmations. These are implemented for the current release-critical admin surfaces; further UX refinement is ongoing product polish rather than a missing framework subsystem.

## P1 - Live Integration Closure

- Payment webhook routes, signature verification, event idempotency, event ledgers, order reconciliation, provider callback documentation, provider-specific env keys, installer fields, readiness metadata, and whole-catalog release checks are implemented.
- Carrier integration seams are implemented through provider-backed adapters in reference mode: label references, service-point lookup, shipment booking, tracking sync, cancellation, admin-native routes, installer/env settings, and first-party coverage for the Swedish carrier catalog.
- Subscription provider event ingestion is implemented for recurring payment success/renewal, failure/dunning, pause, resume, cancellation, idempotency, and renewal-order creation. Remaining live work is provider-specific merchant credentialing and adapter execution where the payment provider does not fully own recurring billing.

## Exact Remaining Work After Current Slice

- P0: run and record the full supported database/cache/session matrix in real environments before release tagging; this workspace currently skips MySQL/PostgreSQL/SQL Server/Redis/Memcached checks because the services/extensions are not provisioned.
- P0: complete final release hygiene by keeping status/test counts current, running `composer release:check`, keeping `Data/*.sql` migration-aligned, and confirming no runtime-generated secrets or local artifacts are tracked. Current local verification is `OK (146 tests, 3196 assertions)` plus `composer release:check` status `200`.
- P1: configure live payment, webhook, subscription, and carrier credentials/endpoints per deployed project. These values intentionally remain outside the released repository.
- P2: run full cross-browser visual/accessibility smoke passes for public and admin templates and fix any findings. Static template accessibility checks and local server smoke for `/`, `/install/`, and theme CSS/JS assets are complete in this workspace.
- P2: keep deployment and upgrade recipes validated against the target host before tagging.
- P2: deepen provider-specific smoke tests once live payment, subscription, carrier, Redis, Memcached, SQL Server, and optional extension environments are provisioned.

## P2 - Production Hardening

- Inventory reservations, expiry, and ledger entries are implemented and visible in admin operations/order views.
- Returns, exchanges, partial refunds, VAT invoices, credit notes, return authorizations, and packing slips are implemented through admin-native workflows.
- Full cross-browser visual/accessibility passes remain target-environment work; static template accessibility checks and local server smoke for installer/theme assets now pass locally.
- Upgrade notes and deployment recipes are now anchored in `Docs/DeploymentAndUpgrade.md` and `RELEASE.md`; expand them per target host as production environments are selected.
- Continue deepening unit and integration tests around live provider adapters, fulfillment strategies, installer output, webhooks, inventory, subscriptions, documents, returns, and cross-database behavior.
