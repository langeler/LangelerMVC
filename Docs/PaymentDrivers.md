# Payment Drivers

This document describes the current first-party payment-driver surface in LangelerMVC as of `2026-04-21`.

## Payment Layer Shape

LangelerMVC keeps the payment layer gateway-agnostic at framework level.

The core payment surface is built around:

- `PaymentManager`
- `PaymentDriverInterface`
- `PaymentIntent`
- `PaymentResult`
- provider-driven driver resolution through `PaymentProvider`

Orders persist the selected driver, payment method family, payment flow, idempotency key, provider/external/webhook references, and next-action payload so checkout, admin, audit, and reconciliation flows all stay aligned.

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
- live-mode merchant onboarding and WSDL/API mapping remain deployment concerns
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

## Testing Posture

The framework regression suite verifies:

- payment driver catalog exposure
- provider selection through the framework manager
- provider-aware checkout persistence in `OrderModule`
- redirect/reconcile/capture lifecycle handling
- admin/payment capability visibility

The repository does not ship vendor SDK integration tests in core. Live-provider verification still depends on project credentials and environment setup.
