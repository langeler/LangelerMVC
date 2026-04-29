# Changelog

All notable LangelerMVC release work is tracked here.

## Unreleased

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
