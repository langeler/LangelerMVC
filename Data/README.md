# Data SQL References

`Data/*.sql` files are release-reference schema snapshots generated from the framework and first-party module migrations.

- The authoritative runtime schema lives in `App/Framework/Migrations` and `App/Modules/*Module/Migrations`.
- These files are SQLite-compatible reference SQL for review, onboarding, and release auditing.
- They intentionally contain no live credentials, secrets, tenant data, or project-specific provider settings.
- Regenerate or update them whenever migrations materially change before tagging a future release.

Current grouped snapshots:

- `Framework.sql`: framework migrations, migration locks, queue jobs, failed jobs, and audit log tables.
- `Web.sql`: WebModule page content table.
- `Users.sql`: identity, RBAC, auth-token, and passkey tables.
- `Products.sql`: catalog category/product tables with fulfillment metadata.
- `Carts.sql`: cart, cart item, promotion, and promotion usage tables.
- `Orders.sql`: order lifecycle, payment, fulfillment, entitlement, subscription, inventory, return, document, and webhook tables.
