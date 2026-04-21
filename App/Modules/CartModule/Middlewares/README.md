# Middlewares

This directory is part of the implemented `CartModule` module contract. Module-specific middleware that runs before or around controller execution.

`CartModule` currently keeps its runtime-sensitive behavior inside the cart request/service flow and the auth-driven merge listener.

That is intentional: guest/auth cart identity, session-backed cart keys, totals, and merge behavior are all enforced through the cart service layer so HTML and JSON entrypoints stay consistent.
