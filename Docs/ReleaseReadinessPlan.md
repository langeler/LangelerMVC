# Release Readiness Plan

This plan tracks the remaining work to move LangelerMVC from late-stage framework completion to a production-ready, plug-and-play release.

## Current Release Position

The framework foundation is implemented: core runtime services, first-party modules, native `.vide` templates, admin workflows, commerce totals, payment driver contracts, Swedish carrier-aware shipping, promotions, health checks, audit tooling, queues, and installer rollback are all present.

The remaining work is the final production layer: release hygiene, installer truth, live integrations, fulfillment breadth, operator polish, and deeper verification.

## P0 - Release Blockers

- Remove tracked runtime/security material from version control and ensure secrets are installer/runtime generated.
- Update stale release documentation, status files, setup references, and test counts before tagging a release.
- Bring `.env.example`, installer defaults, and SettingsManager aliases into parity with the current framework surface.
- Keep the installer as the authoritative first-run path for database, modules, admin account, payments, commerce, fulfillment, queues, mail, auth, and operations.
- Verify the full database/cache/session matrix before release, not only the default local regression suite.

## P1 - Commerce Fulfillment Spectrum

Commerce must model fulfillment strategies, not assume every order is shipped.

- Physical shipped products use carrier shipping, tracking, and shipment lifecycle states.
- Digital downloads skip shipping and should later grant secure download entitlements.
- Virtual or online access purchases skip shipping and should later grant gated content/access entitlements.
- Store pickup and scheduled pickup use pickup fulfillment options rather than carrier delivery.
- Pre-orders need availability dates, customer messaging, and release workflows.
- Subscriptions need plans, recurring payment schedules, renewal orders, payment retry/dunning, pause/resume/cancel, and webhook-driven reconciliation.
- Mixed carts must support physical plus digital/virtual/subscription products while only charging shipping for the physical fulfillment portion.

## P1 - Promotion And Coupon Breadth

Promotions should be treated as rules plus benefits.

- Benefit types should include percentage, fixed amount, currency-specific exact amount, free shipping, fixed shipping rate, and shipping percentage discounts.
- Criteria should include currency, subtotal ranges, item counts, product IDs, product slugs, category IDs, fulfillment types, shipping countries, zones, carriers, shipping options, active windows, and excluded products/types.
- Promotions should eventually move from config-only to admin/database-managed records with audit history, activation windows, usage limits, and reporting.

## P1 - Admin Operator Completion

- Convert raw system/operations pages into structured operator panels.
- Add admin-native promotion/coupon management.
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
