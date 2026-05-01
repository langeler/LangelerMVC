# Commerce And Providers

LangelerMVC ships a broad commerce baseline with plug-and-play provider seams.

## Fulfillment Types

- Physical shipping
- Digital downloads
- Virtual or online access
- Store pickup
- Scheduled pickup
- Pre-order
- Subscriptions and recurring access

Digital, virtual, subscription-only, and other non-shipped fulfillment lines skip shipping charges.

## Payments

First-party payment driver surfaces include:

- Testing/reference
- Card
- PayPal
- Klarna
- Swish
- Qliro
- Walley
- Crypto

The framework provides driver capability discovery, readiness metadata, payment flows, idempotency, webhook ingestion, reconciliation hooks, admin visibility, and environment configuration seams. Live merchant credentials and provider onboarding stay deployment-local.

## Shipping

First-party carrier adapter surfaces include:

- PostNord
- Instabox
- Budbee
- Bring
- DHL
- Schenker
- Early Bird
- Airmee
- UPS
- Mina Paket handoff metadata

Adapters support reference/live mode readiness, service-point lookup, booking, label references, tracking sync, and cancellation seams.

## Promotions, Subscriptions, Inventory, Returns, Documents

The framework includes database-backed promotions and coupons, criteria/benefit breadth, analytics, checkout usage ledgers, subscriptions with dunning and renewal orders, inventory reservations, returns/exchanges, partial refunds, VAT invoices, credit notes, packing slips, and return authorizations.
