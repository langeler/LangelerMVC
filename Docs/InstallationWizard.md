# Installation Wizard

LangelerMVC now ships with a first-party browser-based installation wizard.

The wizard is intended to minimize manual `.env` editing, database preparation friction, and repeated terminal hopping on first setup.

## Entry Point

- HTTP entry: `Public/install/index.php`
- Automatic redirect: `App\Core\Bootstrap` sends normal HTTP traffic into the installer when `APP_INSTALLED=false`

This makes the installer the default first-run experience rather than an optional extra.

## What The Wizard Configures

The wizard collects and writes the runtime configuration needed to boot a real application baseline:

- application identity, URL, locale, and timezone
- database driver and connectivity settings
- session, cache, queue, and mail driver configuration
- crypto / encryption driver defaults
- framework security defaults such as verification and cookie behavior
- first administrator account provisioning
- `WebModule` starter-content mode
- payment driver, payment method family, and default payment flow
- commerce shipping, carrier adapter, pickup/pre-order, subscription, inventory reservation, return, and order-document defaults

## What The Wizard Does

On successful installation it can:

- validate the writable runtime paths
- validate the database connection
- write `.env`
- run migrations
- run seeds
- provision the first-party module baseline
- create the first administrator account
- leave the application in an installed, bootable state

## Status And Capability Surface

The installer also reports readiness and capability signals before writing configuration, including:

- environment file writability
- storage/database path writability
- loaded PHP extensions
- available framework modules
- supported database/session/cache/mail/queue/payment driver options
- whether the framework has already been installed

This gives a clearer first-run experience than trial-and-error config editing.

## Production Notes

Recommended production posture after the wizard completes:

- verify `APP_URL` uses the real public origin
- verify HTTPS and secure cookie settings
- replace development mail/payment credentials with real environment values
- review commerce policy values such as `COMMERCE_INVENTORY_*`, `COMMERCE_RETURNS_*`, `COMMERCE_DOCUMENTS_*`, `COMMERCE_SHIPPING_*`, and `COMMERCE_SUBSCRIPTION_*`
- run the health checks:
  - `php console health:check`
  - `php console health:check ready`
- run the default verification path:
  - `composer test`
  - `composer ops:health`

## Relationship To Manual Configuration

Manual `.env` editing still works and remains fully supported.

The important change is that it is no longer the primary expected setup path. The framework now offers a guided installation path that aligns with:

- configuration isolation
- smoother onboarding
- clearer operational readiness
- reduced setup mistakes

## Template And Presentation Surface

The installer UI itself is part of the native presentation stack:

- layout: `App/Templates/Layouts/InstallerShell.vide`
- page: `App/Templates/Pages/InstallerWizard.vide`
- view: `App/Installer/InstallerView.php`

That means the installer also acts as a first-party reference for the framework-native `.vide` templating system.
