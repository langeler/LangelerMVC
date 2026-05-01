# Installation

## Requirements

- PHP 8.4+
- Composer
- A supported database. SQLite is the fastest first install, while MySQL, PostgreSQL, and SQL Server are supported through the DB-matrix harness.

## Quick Start

```bash
git clone https://github.com/langeler/LangelerMVC.git
cd LangelerMVC
composer install
php -S 127.0.0.1:8000 -t Public Public/index.php
```

Open `http://127.0.0.1:8000`. If `APP_INSTALLED=false`, the framework redirects to the installer at `/install/index.php`.

## Guided Installer

The installer is the preferred first-run path. It writes `.env`, validates the database, prepares storage, runs migrations and seeds, provisions the administrator, and configures framework defaults for modules, payments, shipping, mail, queues, auth, commerce, theme, and operations.

The installer uses a guided stepper:

- Application
- Theme and UX
- Database
- Runtime services
- Mail and notifications
- Security and identity
- Commerce and content
- Administrator

Without JavaScript, all sections remain visible and the install form still posts normally.

## Post-Install Checks

```bash
php console health:check
php console health:check ready
composer ops:health
composer release:check
```

For a deployed project with live integrations, also run:

```bash
php console release:check --strict=1
```

Strict mode is deployment-specific and expects live credentials, seller/VAT data, optional matrix extensions, live provider settings, and browser/accessibility validation.
