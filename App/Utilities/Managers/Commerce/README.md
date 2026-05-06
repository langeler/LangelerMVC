# Commerce Managers

This is the canonical manager layer for ecommerce orchestration.

These classes are shared by `AdminModule`, `CartModule`, `OrderModule`, provider wiring, console checks, and tests. They intentionally live outside individual modules so projects can plug in modules independently while still using one commerce workflow surface.

Manager responsibilities:

- `CartPricingManager`: combines shipping quotes, promotion evaluation, and total calculation for carts.
- `CatalogLifecycleManager`: handles admin-safe publish, unpublish, archive, and delete workflows for catalog entities.
- `EntitlementManager`: grants and synchronizes digital/virtual access after order changes.
- `InventoryManager`: validates, reserves, commits, and releases stock.
- `OrderDocumentManager`: issues invoices, credit notes, packing slips, and return authorizations.
- `OrderLifecycleManager`: coordinates capture, cancel, refund, reconcile, shipment, and delivery transitions.
- `OrderReturnManager`: manages return and exchange requests.
- `PromotionManager`: evaluates config-backed and database-backed promotions/coupons.
- `ShippingManager`: owns commerce-facing shipping, pickup, digital/no-shipping, carrier catalog, and tracking payloads.
- `SubscriptionManager`: manages recurring payment/subscription lifecycle synchronization.

`App/Support/Commerce/*Manager.php` files are retained as backward-compatible aliases. New code should import from `App\Utilities\Managers\Commerce`.
