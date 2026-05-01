# Changelog

All notable LangelerMVC release work is tracked here.

## Unreleased

- No unreleased changes yet.

## 1.0.0 - 2026-05-01

- Added a guided step-based installer UX with section navigation, progress state, previous/next controls, validation-aware step focus, no-JS fallback behavior, and documentation.
- Added canonical repository metadata documentation for release-facing description, topics, about text, package posture, and publication caveats.
- Added framework-wide Bootstrap-compatible theme management with light, dark, and system modes, installer/env settings, shared layout globals, tracked public CSS/JS assets, docs, tests, and release-gate coverage.
- Added Redis/Memcached runtime backend harness commands for cache/session verification where matching PHP extensions and services are available.
- Added first-party carrier adapter contracts, provider wiring, reference/live mode readiness, and PostNord/Instabox/Budbee/Bring/DHL/Schenker/Early Bird/Airmee/UPS adapter coverage.
- Added payment-provider readiness metadata, provider-specific live endpoint/env/installer parity, and whole-catalog payment release checks for testing, card, PayPal, Klarna, Swish, Qliro, Walley, and crypto.
- Added release-reference `Data/*.sql` snapshots generated from migrations, plus stale-table detection in the release gate.
- Added repository consistency coverage for class/file/namespace naming conventions across class-bearing app files.
- Added `release:check` and `verify:release` release gates covering release docs, `.env.example` parity, SQL reference freshness, module/payment/theme surface completeness, critical routes, commerce fulfillment/carrier breadth, native template accessibility heuristics, external matrix readiness, and live integration warnings.
- Added structured admin operations panels for queue, notification, event, payment, health, inventory, return/document, and audit drilldown visibility.
- Added admin bulk promotion workflows with confirmation UX for activation, deactivation, and deletion.
- Added promotion analytics by code, source, currency, customer, customer segment, and day.
- Added inventory reservation ledgers with checkout reservation keys, TTL expiry, order attachment, commit/release handling, and admin/order visibility.
- Added admin-native return and exchange workflows with request, approval, rejection, completion, restock handling, partial refund continuation, and credit-note issuing.
- Added VAT/order document issuing for invoices, credit notes, packing slips, and return authorizations.
- Added installer/environment parity for inventory reservation TTL, return policies, and order-document seller/VAT settings.
- Added DB-backed subscription runtime with subscription records, plan metadata, recurring schedules, trials, retry/dunning state, renewal orders, entitlement synchronization, and admin pause/resume/cancel actions.
- Added signed/idempotent subscription webhook ingestion for renewal/payment-success, payment-failure, pause, resume, and cancellation provider events.
- Added subscription installer/environment parity for trial days, maximum retries, and dunning retry intervals.
- Added customer-aware promotion criteria and usage enforcement for account IDs, customer emails, customer segments, per-customer limits, and per-segment limits.
- Added admin-native carrier operation seams for service-point lookup, shipment booking, label references, tracking sync, shipment cancellation, and auto-booked shipping.
- Added shipping integration environment parity for reference-mode carrier settings and label URL generation.
- Added database-backed promotion and coupon management with admin-native create, update, activate, deactivate, and delete workflows.
- Added promotion persistence tables, model, repository summaries, and runtime catalog integration so database promotions participate in cart and checkout pricing.
- Expanded promotion rule breadth for percentage, fixed amount, free shipping, fixed shipping rate, shipping percentage, currency, item, product, fulfillment, shipping, active-window, and usage-limit criteria.
- Removed tracked secure runtime material from version control and documented `Storage/Secure` as deployment-local generated storage.
- Updated installer/environment parity for queue worker settings, notification defaults, HTTP/auth security keys, commerce inventory flags, operations flags, and list-style environment aliases.
