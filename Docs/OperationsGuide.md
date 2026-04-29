# Operations Guide

This document covers the framework-native operational surfaces that are now part of LangelerMVC itself rather than being left to application-local conventions.

## First-Run Installation

LangelerMVC now ships with a browser-based installation wizard at `Public/install/index.php`.

- When `APP_INSTALLED=false`, `App\Core\Bootstrap` redirects normal HTTP traffic into the installer automatically.
- The installer prepares storage paths, validates database connectivity, writes `.env`, runs migrations + seeds, and provisions the first administrator account.
- The installer also configures first-party payment defaults and the database-backed `WebModule` starter baseline.
- Manual `.env` editing is still supported, but the intended production-first setup path is now the installer rather than hand-editing config files before first boot.

For the full first-run walkthrough, see `InstallationWizard.md`.

## Health Endpoints

LangelerMVC now exposes built-in health endpoints through `App\Core\App`:

- `GET /health`: liveness
- `GET /ready`: readiness

These routes are handled before normal router dispatch when the `health` core service is available.

### What They Report

- `live`: runtime availability, PHP version, SAPI, and application identity
- `ready`: database, cache, session, queue, notification, payment, mail, passkey, audit, and framework-structure checks
- `capabilities`: available drivers, enabled features, registered modules, event/listener visibility, and payment method/flow compatibility

## Console Operations

The current first-party operational commands include:

```bash
php console list
php console health:check
php console health:check ready
php console framework:doctor
php console framework:doctor --strict
php console audit:list --limit=25
php console migrate
php console module:make Blog
php console seed
php console route:list
php console queue:work notifications
php console queue:failed
php console queue:retry
php console event:list
php console notification:list
```

Composer shortcuts are also available:

```bash
composer ops:health
composer ops:ready
composer ops:audit
composer verify:platform
```

## Payment Operations

The framework payment layer is now designed as a gateway-agnostic compatibility surface.

- `PaymentManager` exposes driver capabilities, supported payment method families, and supported payment flows before checkout is attempted.
- First-party provider drivers now ship for:
  - `card`
  - `paypal`
  - `klarna`
  - `swish`
  - `qliro`
  - `walley`
  - `crypto`
  - `testing`
- The framework payment taxonomy covers `card`, `wallet`, `bank_transfer`, `bnpl`, `local_instant`, `manual`, and `crypto` method families across `authorize_capture`, `purchase`, `redirect`, `async`, and `manual_review` flows.
- Redirect/customer-action and asynchronous flows can be reconciled through the framework payment manager rather than module-local gateway code.
- Order records now persist payment method, flow, idempotency key, provider/external/webhook references, and next-action metadata so admin and audit surfaces can inspect them consistently.
- Payment provider callbacks should target `POST /api/orders/webhooks/payments/{driver}` or `POST /orders/webhooks/payments/{driver}`. The framework records each event in `payment_webhook_events`, verifies HMAC signatures through `PaymentManager`, and reconciles matched orders through the same lifecycle path used by admin/manual reconciliation.
- Configure webhook secrets with `PAYMENT_WEBHOOK_SECRET_TESTING`, `PAYMENT_WEBHOOK_SECRET_CARD`, `PAYMENT_WEBHOOK_SECRET_CRYPTO`, `PAYMENT_WEBHOOK_SECRET_PAYPAL`, `PAYMENT_WEBHOOK_SECRET_KLARNA`, `PAYMENT_WEBHOOK_SECRET_SWISH`, `PAYMENT_WEBHOOK_SECRET_QLIRO`, and `PAYMENT_WEBHOOK_SECRET_WALLEY` as needed.
- Live provider execution still depends on merchant onboarding, credentials, certificates, callback URLs, and environment support. The framework ships the reusable driver layer and configuration boundary so those providers stay plug-and-play from the application/module perspective.

## Subscription Operations

The order module now includes a DB-backed subscription runtime for recurring digital, virtual, and access-based purchases.

- Subscription products use `fulfillment_type=subscription` and a product fulfillment policy with plan metadata such as `plan_code`, `plan_label`, `interval`, `interval_count`, `trial_days`, `max_retries`, `dunning_retry_days`, and provider/customer references.
- Captured checkout and payment capture transitions create or activate `order_subscriptions` records alongside digital entitlements.
- Admin order pages expose pause, resume, and cancel actions without leaving the dashboard. Those actions also synchronize the linked entitlement status.
- Provider callbacks should target `POST /api/orders/webhooks/subscriptions/{driver}` or `POST /orders/webhooks/subscriptions/{driver}`. Subscription webhooks reuse the payment webhook signature verifier and event ledger.
- Supported provider event families include renewal/payment success, payment failure, pause, resume, and cancellation. Renewal events create captured renewal orders, reset dunning state, advance the billing period, and keep entitlement access active.
- Dunning configuration is installer/env backed through `COMMERCE_SUBSCRIPTION_TRIAL_DAYS`, `COMMERCE_SUBSCRIPTION_MAX_RETRIES`, and `COMMERCE_SUBSCRIPTION_DUNNING_RETRY_DAYS`.
- Live subscription production use still depends on the selected provider's recurring-billing ownership model, merchant credentials, and callback payload shape. The framework boundary is ready for provider adapters while keeping admin and order behavior consistent.

## Content Operations

The admin dashboard includes WebModule page authoring at `/admin/pages`.

- Operators can create, update, publish, unpublish, and delete database-backed pages without leaving the admin surface.
- The home page is protected from destructive deletion so the public root route always has a safe content anchor.
- Published pages are served by the WebModule database content source; draft pages remain available only through the admin workflow until published.
- Admin page actions emit audit records and framework events for saved, published, unpublished, and deleted pages.

## Promotion Operations

The admin dashboard now includes database-backed promotion and coupon management at `/admin/promotions`.

- Operators can create, update, activate, deactivate, and delete promotions without leaving the admin surface.
- Runtime pricing merges config-backed baseline promotions with database-backed admin promotions, with database records taking precedence by code.
- Supported benefit families include percentage, fixed amount, free shipping, fixed shipping rate, and shipping percentage discounts.
- Supported criteria include currency, subtotal ranges, item counts, product IDs/slugs, categories, fulfillment types, shipping countries/zones/carriers/options, customer accounts, customer emails, customer segments, active windows, exclusions, free-shipping eligibility, global usage limits, per-customer limits, and per-segment limits.
- Checkout records promotion usage ledgers with order/cart/user context and increments database-backed usage counters for operational limit enforcement.
- Promotion usage ledgers include customer email and customer segment context when available, allowing later applications to enforce per-customer and per-segment limits without application-local coupon code.
- Admin promotion metrics include recent usage records and aggregate discount totals.
- Config-backed promotions remain useful for immutable baseline/demo promotions; database-backed promotions are the production operator workflow.

## Shipping Operations

Admin order pages now expose carrier operations without leaving the admin surface.

- Supported reference carriers include PostNord, Instabox, BudBee, Bring, DHL, Schenker, Early Bird, Airmee, and UPS, with Mina Paket surfaced as a Swedish tracking-app handoff where applicable.
- Operators can look up service points, book a shipment, create a label reference, mark an order shipped, sync tracking, cancel a shipment booking, and mark delivery through `/admin/orders/{id}` actions.
- `COMMERCE_SHIPPING_INTEGRATION_MODE=reference` keeps the default adapter deterministic and safe for local/demo installs.
- `COMMERCE_SHIPPING_AUTO_BOOK_LABELS=true` lets shipping auto-book a reference label when the operator ships without entering a tracking number.
- `COMMERCE_SHIPPING_LABEL_FORMAT` and `COMMERCE_SHIPPING_LABEL_BASE_URL` control the generated label reference URL in reference mode.
- Live carrier production use should swap this boundary for provider credentials/API calls while preserving the same admin routes and lifecycle actions.

## Audit Logging

The framework now ships with a built-in audit logger backed by the `framework_audit_log` table.

Current first-party audit events include:

- authentication registration, sign-in/sign-out, password-reset, and email-verification lifecycle events
- OTP enable/disable, recovery regeneration, and trusted-device actions
- passkey registration, authentication, and deletion
- role and permission synchronization from the admin surface
- WebModule page save, publish, unpublish, and delete actions from the admin surface
- promotion creation/update, activation, deactivation, and deletion from the admin surface
- order creation and payment-state transitions
- subscription sync, pause, resume, cancel, renewal, and dunning events

Audit logging is configured through `Config/operations.php`.

## Trusted Devices

TOTP now supports trusted-device / remember-device behavior through the framework identity layer.

- trusted-device tokens are persisted through `UserAuthTokenRepository`
- the browser token is stored in the configured OTP trusted-device cookie
- trusted devices can be revoked from the user profile flow
- profile payloads now expose trusted-device visibility for both HTML and JSON responses

The main settings live in `Config/auth.php`:

- `OTP.TRUSTED_DEVICE_DAYS`
- `OTP.TRUSTED_DEVICE_COOKIE`

## Local Verification Stack

LangelerMVC now includes a local backend verification stack in `docker-compose.verify.yml`.

Services provided:

- MySQL
- PostgreSQL
- SQL Server
- Redis
- Memcached

Typical usage:

```bash
docker compose -f docker-compose.verify.yml up -d
composer test
composer test:db-matrix
composer test:mysql
composer test:pgsql
composer test:sqlsrv
composer ops:health
```

Redis, Memcached, and Imagick verification still depend on the relevant PHP extensions being available in the environment where the framework tests are executed.

## CI Posture

GitHub Actions now provides:

- default regression coverage through `.github/workflows/php.yml`
- supported DB-matrix coverage for MySQL and PostgreSQL in CI
- explicit composer metadata/platform checks before regression or matrix execution
- PHP-side readiness waits for hosted MySQL/PostgreSQL services
- target diagnostics and DB service log artifacts on failure

SQL Server verification remains part of the local/containerized workflow because hosted runner support can vary more across environments than the framework code itself.
