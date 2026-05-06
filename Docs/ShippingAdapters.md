# Shipping Adapters

This document describes the first-party carrier adapter surface in LangelerMVC as of `2026-04-30`.

## Adapter Layer Shape

Shipping is now routed through a provider-managed adapter boundary instead of being hardcoded inside order/admin workflows.

The core shipping surface is built around:

- `CarrierAdapterInterface`
- `CarrierAdapter`
- `ShippingProvider`
- `App\Utilities\Managers\Commerce\ShippingManager`

`App\Utilities\Managers\Commerce\ShippingManager` owns commerce-facing quoting, fulfillment decisions, pickup/digital delivery selection, order snapshots, and presentation payloads. `App\Support\Commerce\ShippingManager` remains as a thin backward-compatible alias. Carrier-specific operational actions now go through carrier adapters:

- service-point lookup
- shipment booking
- label reference creation
- tracking sync
- shipment cancellation

This keeps `AdminModule`, `CartModule`, and `OrderModule` stable while projects swap reference adapters for live provider credentials or project-specific adapter classes.

## First-Party Carrier Adapters

The framework currently ships adapters for:

- `postnord`
- `instabox`
- `budbee`
- `bring`
- `dhl`
- `schenker`
- `earlybird`
- `airmee`
- `ups`

These match the Swedish-first carrier catalog used by the commerce configuration and operations UI. Mina Paket remains modeled as a tracking-app handoff surfaced where carrier/country support applies.

## Reference Mode And Live Mode

Every first-party carrier adapter supports two modes:

- `reference`
- `live`

### Reference Mode

Reference mode is the default repository posture.

It provides deterministic behavior for:

- service-point lookup
- shipment booking
- label reference URLs
- tracking number generation
- tracking state progression
- shipment cancellation

This makes local installs and regression tests safe without external carrier credentials.

### Live Mode

Live mode is configured through `Config/commerce.php` and `.env` overrides.

The generic live adapter settings are:

- `COMMERCE_SHIPPING_INTEGRATION_MODE=live`
- `COMMERCE_SHIPPING_ACTIVE_CARRIER`
- `COMMERCE_SHIPPING_API_BASE`
- `COMMERCE_SHIPPING_API_KEY`
- `COMMERCE_SHIPPING_SERVICE_POINTS_URL`
- `COMMERCE_SHIPPING_BOOKING_URL`
- `COMMERCE_SHIPPING_TRACKING_URL`
- `COMMERCE_SHIPPING_CANCELLATION_URL`
- `COMMERCE_SHIPPING_TIMEOUT`

Provider-specific credentials can be supplied in `Config/commerce.php` under `SHIPPING.ADAPTERS.{carrier}` or by extending `ShippingProvider` with a project adapter class.

Live mode intentionally fails closed when required endpoint or credential settings are missing. The release gate reports reference-mode carrier posture as a strict-mode warning until live carrier validation is completed in the target environment.

## Carrier Matrix

| Carrier | Typical Use | Service Levels | Regions |
| --- | --- | --- | --- |
| `postnord` | Swedish/Nordic service-point and home delivery | `service_point`, `home` | `SE`, `NORDIC`, `EU` |
| `instabox` | Swedish locker delivery | `locker` | `SE` |
| `budbee` | Swedish/Nordic locker and home delivery | `locker`, `home` | `SE`, `NORDIC` |
| `bring` | Nordic service-point and home delivery | `service_point`, `home` | `SE`, `NORDIC`, `EU` |
| `dhl` | Service-point, home, and express delivery | `service_point`, `home`, `express` | `SE`, `NORDIC`, `EU`, `INTL` |
| `schenker` | Parcel/service-point delivery | `service_point`, `home` | `SE`, `NORDIC`, `EU` |
| `earlybird` | Swedish mailbox delivery | `mailbox` | `SE` |
| `airmee` | Swedish home delivery | `home` | `SE` |
| `ups` | Standard, home, and express delivery | `home`, `standard`, `express` | `SE`, `NORDIC`, `EU`, `INTL` |

## Extension Pattern

Projects can register their own carrier adapter without changing framework modules:

```php
$provider = new \App\Providers\ShippingProvider();
$provider->extendAdapter('my_carrier', \App\Shipping\MyCarrierAdapter::class);
$provider->registerServices();
```

The adapter class must implement `CarrierAdapterInterface`. Extending `CarrierAdapter` gives a safe reference-mode implementation plus live endpoint helpers.

## Operational Surfaces

Carrier compatibility is visible through:

- installer carrier compatibility panel
- admin operations carrier adapter panel
- admin order carrier actions
- `ShippingManager::carrierCatalog()`
- `ShippingManager::adapterCatalog()`
- `HealthManager` capability reporting
- `release:check` commerce-surface and live-integration checks

## Testing Posture

The regression suite verifies:

- first-party adapter catalog coverage
- service-point lookup
- shipment booking
- label reference creation
- tracking sync
- shipment cancellation
- live-mode readiness failure when required settings are missing
- project-specific adapter registration through `ShippingProvider`

Live carrier integration tests remain environment-specific because credentials, endpoints, labels, and pickup-location APIs differ by carrier account and merchant onboarding.
