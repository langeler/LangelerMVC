# Release Readiness Plan

This plan tracks the remaining work to move LangelerMVC from late-stage framework completion to a production-ready, plug-and-play release.

## Current Release Position

The framework foundation is implemented: core runtime services, first-party modules, native `.vide` templates, admin workflows, commerce totals, payment driver contracts, Swedish carrier-aware shipping, promotions, health checks, audit tooling, queues, and installer rollback are all present.

The remaining work is the final production layer: release hygiene, installer truth, live integrations, fulfillment breadth, operator polish, and deeper verification.

## P0 - Release Blockers

- Remove tracked runtime/security material from version control and ensure secrets are installer/runtime generated. Current slice removes the tracked secure cache key and keeps `Storage/Secure` ignored except for its README.
- Update stale release documentation, status files, setup references, and test counts before tagging a release. `CHANGELOG.md` and `RELEASE.md` now exist as release-facing anchors.
- Bring `.env.example`, installer defaults, and SettingsManager aliases into parity with the current framework surface. Current slice adds queue worker, notification, HTTP/auth, operations, commerce inventory, and list-style env handling coverage.
- Keep the installer as the authoritative first-run path for database, modules, admin account, payments, commerce, fulfillment, queues, mail, auth, and operations.
- Verify the full database/cache/session matrix before release, not only the default local regression suite.

## P1 - Commerce Fulfillment Spectrum

Commerce must model fulfillment strategies, not assume every order is shipped.

- Physical shipped products use carrier shipping, tracking, and shipment lifecycle states.
- Digital downloads skip shipping and now grant order-scoped download entitlements with access keys, limits, windows, and admin revoke/reactivate controls.
- Virtual or online access purchases skip shipping and use the same entitlement foundation for gated content/access URLs.
- Store pickup and scheduled pickup use pickup fulfillment options rather than carrier delivery.
- Pre-orders need availability dates, customer messaging, and release workflows.
- Subscriptions need plans, recurring payment schedules, renewal orders, payment retry/dunning, pause/resume/cancel, and webhook-driven reconciliation.
- Mixed carts must support physical plus digital/virtual/subscription products while only charging shipping for the physical fulfillment portion.

## P1 - Promotion And Coupon Breadth

Promotions should be treated as rules plus benefits.

- Benefit types should include percentage, fixed amount, currency-specific exact amount, free shipping, fixed shipping rate, and shipping percentage discounts.
- Criteria should include currency, subtotal ranges, item counts, product IDs, product slugs, category IDs, fulfillment types, shipping countries, zones, carriers, shipping options, active windows, and excluded products/types.
- Promotions now have database-managed admin records with audit events, activation windows, usage limits, and runtime catalog integration. Remaining breadth is deeper reporting, usage ledgers, and per-customer/per-segment limits.

## P1 - Admin Operator Completion

- Convert raw system/operations pages into structured operator panels.
- Add admin-native promotion/coupon management. Current slice adds protected admin routes, controller actions, service workflows, resource payloads, and native `.vide` templates for promotion operations.
- Add WebModule page authoring and publishing.
- Add richer filters, bulk actions, audit drilldowns, and lifecycle confirmations.

## P1 - Live Integration Closure

- Add payment webhook routes, signature verification, event idempotency, and provider callback documentation.
- Add carrier integration seams for labels, pickup/service-point lookup, booking, tracking sync, and cancellation.
- Add subscription provider events for recurring payment success, failure, retry, cancellation, and renewal.

## P2 - Production Hardening

- Add inventory reservations, expiry, and ledger entries instead of direct stock decrement only.
- Add returns, exchanges, partial refunds, VAT invoices, and order documents.
- Add accessibility, responsive, and browser smoke passes for public and admin templates.
- Add `CHANGELOG.md`, `RELEASE.md`, upgrade notes, and deployment recipes.
- Deepen unit and integration tests around promotions, fulfillment strategies, installer output, webhooks, inventory, subscriptions, and cross-database behavior.
