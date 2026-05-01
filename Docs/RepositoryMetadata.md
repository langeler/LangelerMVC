# Repository Metadata

Use this page as the canonical release-facing repository metadata source when updating GitHub, Packagist, or another package registry.

## Repository Identity

- Repository name: `LangelerMVC`
- Owner: `langeler`
- Package name: `langeler/mvc`
- Homepage: `https://github.com/langeler/LangelerMVC`
- License: `MIT`
- Primary language/runtime: `PHP 8.4+`

## Description

LangelerMVC is a modular PHP 8.4 MVC framework with first-party auth, passkeys, admin operations, commerce, shipping, and plug-and-play provider integration.

## About

Production-ready modular PHP MVC framework with native `.vide` templates, first-party Web/User/Admin/Shop/Cart/Order modules, installer-led setup, auth/RBAC/TOTP/passkeys, queues, notifications, payments, Swedish carrier-aware shipping, subscriptions, promotions, inventory, returns, VAT/order documents, health checks, audit tooling, and release gates.

## Recommended GitHub Topics

GitHub currently supports a limited topic set per repository. Use this curated release set:

- `php`
- `php84`
- `mvc`
- `framework`
- `modular`
- `oop`
- `rbac`
- `passkeys`
- `webauthn`
- `queue`
- `notifications`
- `payments`
- `ecommerce`
- `shipping`
- `subscriptions`
- `installer`
- `paypal`
- `klarna`
- `swish`
- `crypto`

## Release Position

- Framework package release gate: `composer release:check`
- Full local release verification: `composer verify:release`
- Project go-live strict gate: `php console release:check --strict=1`
- Strict mode is expected to remain deployment-dependent until live payment, subscription, carrier, VAT/legal, optional extension, matrix, and browser/accessibility checks are completed in the target environment.

## Package Publication

The repository is currently a Composer `project` package. A GitHub release tag is appropriate for framework source distribution. Publishing to Packagist or GitHub Packages is appropriate only after the maintainer confirms the desired distribution channel and registry credentials outside the repository.
