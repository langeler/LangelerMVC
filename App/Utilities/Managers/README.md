# Managers

This directory is the canonical home for framework manager classes.

Managers are concrete orchestration utilities that are shared by modules, providers, commands, and framework runtime services. New manager classes should live in a focused subdirectory here instead of being placed under `App/Support` or inside an unrelated module.

Current canonical sublayers:

- `Async`: events, queues, and failed-job storage.
- `Commerce`: cart pricing, catalog lifecycle, entitlements, inventory, order lifecycle/documents/returns, promotions, shipping, and subscriptions.
- `Data`: cache, crypto, module, and session data managers.
- `Presentation`: assets, safe HTML helpers, themes, and the native `.vide` template engine.
- `Security`: auth, guard, gate, policy, permission, signed URL, and throttle services.
- `Support`: audit, health, mail, notification, OTP, passkey, and payment managers.
- `System`: file, settings, date/time, iterator, reflection, compression, and error managers.

Legacy paths may remain as thin aliases when needed for existing projects, but first-party framework code should import from this directory.
