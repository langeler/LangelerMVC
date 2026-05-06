# Release Status

## Published Release

- Release: [v1.0.0](https://github.com/langeler/LangelerMVC/releases/tag/v1.0.0)
- Published: 2026-05-01
- Local verification: `composer verify:release`
- Regression result: `OK (158 tests, 3325 assertions)`
- Architecture gate: `composer architecture:check`
- Release gate: `composer release:check` returns `status=200`

## Release Meaning

`v1.0.0` is framework/package ready. The framework runtime, modules, installer, provider seams, docs, and release gates are complete and verified.

Project production go-live remains deployment-specific. Each installed project must provide live payment, subscription, carrier, webhook, seller/VAT, optional extension, matrix, and browser/accessibility validation in its own environment.

## Verification Commands

```bash
composer test
composer verify:platform
composer architecture:check
composer release:check
composer verify:release
composer test:db-matrix
composer test:runtime-backends
```

`composer test:db-matrix` and `composer test:runtime-backends` depend on external services and PHP extensions being available.
