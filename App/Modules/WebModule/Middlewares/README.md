# Middlewares

This directory is part of the implemented `WebModule` module contract. Module-specific middleware that runs before or around controller execution.

`WebModule` does not currently require dedicated route middleware.

Installer redirects are handled by the bootstrap layer, route misses are handled by the WebModule fallback controller/service path, and starter-page resolution stays inside the page service so the reference slice remains simple and explicit.
