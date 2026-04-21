# Repositories

This directory is part of the implemented `AdminModule` module contract. Persistence adapters that isolate module data access.

`AdminModule` currently composes the repositories owned by the runtime, identity, catalog, cart, and order subsystems.

That is intentional: admin flows inspect and manage existing application state rather than introduce a second persistence layer just for the operator console.
