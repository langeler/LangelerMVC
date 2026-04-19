# Security Policy

## Supported Versions

LangelerMVC is currently maintained on the latest tracked framework line in this repository.

Security fixes are expected to land on the current primary branch first. If older branches are introduced later, support windows should be documented here explicitly.

## Reporting A Vulnerability

Please do not open public GitHub issues for unpatched security vulnerabilities.

Instead, report security issues privately to the maintainer:

- GitHub: [@langeler](https://github.com/langeler)

When reporting, include:

- the affected framework area or module
- reproduction steps or a proof of concept
- impact assessment if known
- environment details such as PHP version, driver/backend, and configuration assumptions

You will receive acknowledgement as quickly as possible, and the issue will be reviewed privately before coordinated disclosure.

## Security Scope

Security-sensitive areas in this repository include, but are not limited to:

- `Public/` entrypoint and web-server configuration
- bootstrap/runtime configuration loading
- session drivers and session encryption
- authentication, RBAC, TOTP, trusted-device, and passkey flows
- signed URLs and HTTP throttling
- crypto, cache, queue, notification, and payment boundaries
- admin/operator surfaces and audit logging

## Hardening Guidance

For production deployments:

- keep PHP and dependencies up to date
- point the web server document root to `Public/`
- review `.env` and `Config/*.php` settings before deployment
- use strong secrets and rotate them when appropriate
- verify passkey RP/origin settings and OTP trusted-device policy
- provision only the drivers and extensions you actually intend to run
- use the documented verification commands before release

See `Docs/OperationsGuide.md` and `Docs/DatabaseMatrixTesting.md` for operational verification guidance.
