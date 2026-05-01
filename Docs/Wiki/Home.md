# LangelerMVC Wiki

LangelerMVC is a released modular PHP 8.4 MVC framework with first-party auth, passkeys, admin operations, commerce, shipping, subscriptions, promotions, inventory, returns, VAT/order documents, health checks, audit tooling, release gates, and a guided installer.

## Current Release

- Current framework source release: [v1.0.0](https://github.com/langeler/LangelerMVC/releases/tag/v1.0.0)
- Release date: 2026-05-01
- Repository: [langeler/LangelerMVC](https://github.com/langeler/LangelerMVC)
- Package name: `langeler/mvc`
- Runtime: PHP 8.4+
- Local release gate: `composer release:check`
- Full verification gate: `composer verify:release`

## What Is Included

- Core runtime, bootstrap, container, routing, config, session, cache, crypto, query, migration, seed, console, health, and audit layers.
- Native `.vide` templating with shared layouts, pages, partials, and components.
- First-party `WebModule`, `UserModule`, `AdminModule`, `ShopModule`, `CartModule`, and `OrderModule`.
- Guided installer with step navigation, progress state, readiness panels, validation-aware focus, and no-JS fallback.
- Payment provider surfaces for testing, card, PayPal, Klarna, Swish, Qliro, Walley, and crypto.
- Swedish carrier-aware shipping adapters for PostNord, Instabox, Budbee, Bring, DHL, Schenker, Early Bird, Airmee, UPS, and Mina Paket handoff metadata.

## Wiki Pages

- [Installation](Installation)
- [Release Status](Release-Status)
- [Operations](Operations)
- [Commerce And Providers](Commerce-And-Providers)
- [Documentation Index](Documentation-Index)
