# Payment Drivers

This document describes the current first-party payment-driver surface in LangelerMVC as of `2026-04-30`.

## Payment Layer Shape

LangelerMVC keeps the payment layer gateway-agnostic at framework level.

The core payment surface is built around:

- `PaymentManager`
- `PaymentDriverInterface`
- `PaymentProvider`
- `PaymentIntent`
- `PaymentResult`
- provider-driven driver resolution through `PaymentProvider`

Orders persist the selected driver, payment method family, payment flow, idempotency key, provider/external/webhook references, and next-action payload so checkout, admin, audit, and reconciliation flows all stay aligned.

Provider callbacks enter through the framework webhook ingestion surface and reconcile through the same payment lifecycle path as manual/admin reconciliation. This keeps async provider events, admin actions, audit logging, and order state transitions on one consistent pipeline.

## First-Party Drivers

The framework currently ships these drivers:

- `testing`
- `card`
- `paypal`
- `klarna`
- `swish`
- `qliro`
- `walley`
- `crypto`

## Payment Method Taxonomy

The framework-normalized method families are:

- `card`
- `wallet`
- `bank_transfer`
- `bnpl`
- `local_instant`
- `manual`
- `crypto`

## Payment Flow Taxonomy

The framework-normalized flow types are:

- `authorize_capture`
- `purchase`
- `redirect`
- `async`
- `manual_review`

## Driver Matrix

| Driver | Typical Use | Methods | Flows | Regions |
| --- | --- | --- | --- | --- |
| `testing` | reference/contract testing | `card`, `wallet`, `bank_transfer`, `bnpl`, `local_instant`, `manual`, `crypto` | `authorize_capture`, `purchase`, `redirect`, `async`, `manual_review` | `GLOBAL` |
| `card` | credit/debit card adapter boundary | `card` | `authorize_capture`, `purchase`, `redirect` | `GLOBAL` |
| `paypal` | PayPal wallet/card checkout | `wallet`, `card` | `authorize_capture`, `purchase`, `redirect` | `GLOBAL` |
| `klarna` | Klarna BNPL checkout | `bnpl` | `redirect`, `authorize_capture` | `SE`, `NO`, `FI`, `DK`, `DE`, `AT`, `NL`, `BE`, `UK`, `US` |
| `swish` | Swedish instant payments | `local_instant` | `redirect`, `async` | `SE` |
| `qliro` | Nordic checkout flows | `card`, `bnpl`, `local_instant`, `bank_transfer` | `redirect`, `authorize_capture` | `SE`, `NO`, `FI`, `DK` |
| `walley` | Nordic BNPL checkout | `bnpl` | `redirect`, `authorize_capture` | `SE`, `NO`, `FI`, `DK` |
| `crypto` | BTC/ETH-style invoice flows | `crypto` | `async`, `redirect`, `manual_review` | `GLOBAL` |

## Reference Mode And Live Mode

Every first-party driver is designed to work in one of two modes:

- `reference`
- `live`

### Reference Mode

Reference mode is the default tracked posture in the repository.

It provides:

- deterministic framework-level behavior for tests and local verification
- realistic next-action payloads for redirect, SDK, Swish, iframe, or crypto invoice flows
- stable driver capability inspection without external credentials

### Live Mode

Live mode is enabled through `Config/payment.php` and provider-specific settings.

Live mode depends on environment readiness outside the framework codebase, for example:

- merchant accounts and provider onboarding
- API credentials or shared secrets
- callback URLs
- client certificates and keys where required
- provider-specific endpoint access

The framework owns the contract boundary and lifecycle handling. Merchant onboarding and deployment-specific secrets remain environment concerns.

Every first-party driver now reports release-readiness metadata through `PaymentDriverInterface::readiness()` and `PaymentManager::driverCatalog()`:

- configured mode
- required live settings
- missing live settings
- `live_ready`
- supported methods and flows

This mirrors the shipping adapter posture and allows the installer, admin operations panel, health/reporting surfaces, and `release:check` to verify the whole payment provider catalog without requiring live credentials in the repository.

## Live Environment Keys

`.env.example`, the installer defaults, and `SettingsManager` expose the provider-specific settings needed to switch a project from reference mode to live mode.

| Driver | Primary live keys |
| --- | --- |
| `card` | `PAYMENT_CARD_API_KEY`, `PAYMENT_CARD_CREATE_URL`, `PAYMENT_CARD_CAPTURE_URL`, `PAYMENT_CARD_REFUND_URL`, `PAYMENT_CARD_CANCEL_URL` |
| `paypal` | `PAYMENT_PAYPAL_API_BASE`, `PAYMENT_PAYPAL_CLIENT_ID`, `PAYMENT_PAYPAL_CLIENT_SECRET`, `PAYMENT_PAYPAL_RETURN_URL`, `PAYMENT_PAYPAL_CANCEL_URL` |
| `klarna` | `PAYMENT_KLARNA_API_BASE`, `PAYMENT_KLARNA_USERNAME`, `PAYMENT_KLARNA_PASSWORD`, `PAYMENT_KLARNA_PURCHASE_COUNTRY`, `PAYMENT_KLARNA_PURCHASE_CURRENCY` |
| `swish` | `PAYMENT_SWISH_API_BASE`, `PAYMENT_SWISH_PAYEE_ALIAS`, `PAYMENT_SWISH_CERTIFICATE_PATH`, `PAYMENT_SWISH_PRIVATE_KEY_PATH`, `PAYMENT_SWISH_CALLBACK_URL` |
| `qliro` | `PAYMENT_QLIRO_API_BASE`, `PAYMENT_QLIRO_API_KEY`, `PAYMENT_QLIRO_MERCHANT_CONFIRMATION_URL`, `PAYMENT_QLIRO_MERCHANT_TERMS_URL` |
| `walley` | `PAYMENT_WALLEY_CREATE_URL`, `PAYMENT_WALLEY_CAPTURE_URL`, `PAYMENT_WALLEY_REFUND_URL`, `PAYMENT_WALLEY_CANCEL_URL`, `PAYMENT_WALLEY_RECONCILE_URL` |
| `crypto` | `PAYMENT_CRYPTO_DEFAULT_ASSET`, `PAYMENT_CRYPTO_DEFAULT_NETWORK`, `PAYMENT_CRYPTO_CONFIRMATIONS_REQUIRED` |

Webhook secrets remain per deployment and are intentionally empty in the repository:

- `PAYMENT_WEBHOOK_SECRET_TESTING`
- `PAYMENT_WEBHOOK_SECRET_CARD`
- `PAYMENT_WEBHOOK_SECRET_CRYPTO`
- `PAYMENT_WEBHOOK_SECRET_PAYPAL`
- `PAYMENT_WEBHOOK_SECRET_KLARNA`
- `PAYMENT_WEBHOOK_SECRET_SWISH`
- `PAYMENT_WEBHOOK_SECRET_QLIRO`
- `PAYMENT_WEBHOOK_SECRET_WALLEY`

## Webhook Ingestion

Payment webhooks are first-party framework surfaces rather than provider-specific module routes.

Public callback routes:

- `POST /orders/webhooks/payments/{driver}`
- `POST /api/orders/webhooks/payments/{driver}`

Runtime behavior:

- routes are intentionally CSRF-exempt because payment providers cannot use browser CSRF tokens
- `PaymentManager` verifies HMAC SHA-256 signatures using `Config/payment.php` webhook settings
- accepted events are recorded in `payment_webhook_events`
- duplicate events short-circuit idempotently by `{driver, event_id}`
- matched orders reconcile through `OrderLifecycleManager::transition(..., 'reconcile')`
- unmatched events are retained for operational review rather than disappearing silently

Default framework signature headers:

- `X-Langeler-Signature`: accepts either `sha256=<hex>` or raw hex HMAC
- `X-Langeler-Event`: provider event identifier used for idempotency
- `X-Langeler-Timestamp`: optional freshness check against `TOLERANCE_SECONDS`

Deployment configuration should set the relevant `PAYMENT_WEBHOOK_SECRET_*` environment variable before exposing a live callback route. Signature enforcement is enabled by default.

## Provider Notes

### `card`

- acts as a framework-native adapter boundary for credit/debit card integrations
- supports direct authorize/capture flows and redirect-style customer action flows
- can be pointed at an external REST-style card gateway through configuration

### `paypal`

- supports wallet/card semantics through the framework payment manager
- supports redirect/customer-action behavior, idempotency, partial capture, and partial refund semantics
- official docs: [PayPal Orders API](https://developer.paypal.com/docs/api/orders/v2/)

### `klarna`

- focused on BNPL flows
- supports redirect/SDK-style customer action and order-management semantics
- official docs: [Klarna Payments](https://docs.klarna.com/payments/)

### `swish`

- focused on Swedish local-instant payment flows
- supports reference-mode Swish app/QR behavior and live-mode certificate-backed requests
- official docs: [Swish Developer](https://developer.swish.nu/)

### `qliro`

- supports multiple Nordic checkout method families through one driver surface
- official docs: [Qliro Developers](https://developers.qliro.com/docs)

### `walley`

- focused on Nordic BNPL checkout flows
- current core driver supports the framework lifecycle and configuration surface without shipping vendor SDKs in core
- live mode can use configured lifecycle endpoints for checkout creation, capture, refund, cancel, and reconciliation, or a project can wrap Walley SOAP/WSDL onboarding behind those endpoints
- merchant onboarding and credential material remain deployment concerns
- official docs: [Walley Payments API](https://dev.walleypay.com/paymentsApi/)

### `crypto`

- supports BTC/ETH-style invoice/reconciliation behavior at framework level
- intended for gateway adapters or direct on-chain merchant flows without coupling modules to provider-specific code
- reference mode simulates invoice creation, customer action, reconciliation, and refund intent recording

## Order Integration

`OrderModule` is now payment-driver-aware as well as payment-method-aware.

That means checkout can persist and later operate on:

- selected driver
- selected method family
- selected flow type
- idempotency key
- provider/external/webhook references
- customer-action metadata

This makes payment capture, cancel, refund, reconcile, admin visibility, and audit logging deterministic across supported providers.

## Operational Surfaces

Payment compatibility is visible through:

- checkout payloads
- order payloads
- `AdminModule` system/operations views
- `HealthManager` capability reporting
- audit logging for order/payment transitions
- webhook event ledgers for async provider callbacks

## Testing Posture

The framework regression suite verifies:

- payment driver catalog exposure
- payment driver readiness metadata and standalone project-driver configuration through `PaymentProvider`
- provider selection through the framework manager
- provider-aware checkout persistence in `OrderModule`
- redirect/reconcile/capture lifecycle handling
- webhook signature rejection, event recording, idempotency, and order reconciliation
- admin/payment capability visibility

The repository does not ship vendor SDK integration tests in core. Live-provider verification still depends on project credentials and environment setup.
