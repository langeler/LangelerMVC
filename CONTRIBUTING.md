# Contributing

Thanks for contributing to LangelerMVC.

This repository is maintained as a production-oriented framework codebase, so changes should preserve the existing design goals:

- modularity and flexibility
- scalability and clear extension seams
- maintainability through SRP and separation of concerns
- readability through explicit structure and typed abstractions
- security through thin public/runtime boundaries and framework-managed sensitive flows
- performance through lazy resolution and predictable runtime behavior
- consistent documentation and verification

## Development Workflow

1. Work from the canonical runtime surfaces:
   - `Public/index.php`
   - `bootstrap/app.php`
   - `console`
   - `bootstrap/console.php`
2. Preserve the provider-driven, module-first architecture.
3. Extend existing contracts, abstract bases, providers, managers, and modules instead of bypassing them.
4. Keep third-party integrations behind framework-native boundaries such as mail, OTP, passkeys, notifications, queues, and payments.
5. Update documentation when behavior, architecture, or verification posture changes.

## Verification Expectations

Before opening or updating a pull request, run the verification commands that match your change:

```bash
composer validate --no-check-publish
composer test
composer ops:health
```

If your changes affect non-SQLite persistence behavior or runtime backends, also run the relevant environment-backed checks when those services are available:

```bash
composer test:db-matrix
composer test:mysql
composer test:pgsql
composer test:sqlsrv
composer ops:ready
```

For local backend provisioning, use:

```bash
docker compose -f docker-compose.verify.yml up -d
```

## Coding Expectations

- Keep responsibilities narrow and avoid cross-layer leakage.
- Prefer framework helpers, managers, and traits over ad hoc native duplication.
- Add or update regression coverage for framework behavior changes.
- Do not introduce vendor-specific payment or infrastructure logic into framework core abstractions.
- Keep HTML and JSON parity intact across first-party modules.
- Preserve module structure conventions under `App/Modules/*`.

## Documentation Expectations

The following docs should stay aligned with meaningful framework changes:

- `readme.md`
- `Docs/ArchitectureOverview.md`
- `Docs/FrameworkStatus.md`
- `Docs/ModulesStructure.md`
- `Docs/OperationsGuide.md`
- `Docs/DatabaseMatrixTesting.md`

If you add or remove tracked top-level or architectural files, update `Docs/CompleteStructure.md` as well.

## Pull Requests

When preparing a pull request:

- keep the scope focused
- describe the framework layer or module being changed
- include the verification commands you ran
- call out any environment-dependent items you could not execute locally

If a change affects CI behavior, database matrix execution, or runtime backends, include that explicitly in the PR summary.
