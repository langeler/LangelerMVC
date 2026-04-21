# Middlewares

This directory is part of the implemented `OrderModule` module contract. Module-specific middleware that runs before or around controller execution.

`OrderModule` currently relies on shared authentication middleware plus module-owned service checks for order ownership, payment transition rules, throttling, and checkout invariants.

That keeps the visible behavior identical across HTML and JSON order flows without duplicating authorization logic between route middleware and the order service.
