# Documentation Index

This folder contains the current LangelerMVC project documentation plus a small set of historical reference files kept in the repository for context.

As of `2026-04-28`, the framework status, release readiness, and architecture documents below are the primary source of truth for what is implemented today.

## Start Here

- `../readme.md`: project overview, installation, run commands, and quick links into the documentation set.
- `../CONTRIBUTING.md`: contributor workflow, verification expectations, and coding standards for framework work.
- `../SECURITY.md`: supported versions and responsible vulnerability disclosure guidance.
- `../CHANGELOG.md`: release-facing change history.
- `../RELEASE.md`: production preflight and tagging checklist.
- `ArchitectureOverview.md`: framework architecture, layer responsibilities, request lifecycle, subsystem map, and extension points.
- `FrameworkStatus.md`: current implementation status, verified coverage snapshot, and remaining environment-dependent hardening areas.
- `ReleaseReadinessPlan.md`: current P0/P1/P2 completion map and remaining release gaps.

## Structure And Layout

- `FolderStructure.md`: the current repository layout by layer and responsibility.
- `ModulesStructure.md`: module conventions, module loading, and the current state of each module.
- `CompleteStructure.md`: a full repository tree snapshot kept for visual orientation.

## Subsystem And Reference Docs

- `SanitationValidationAPI.md`: current schema contract for sanitizers and validators.
- `DatabaseMatrixTesting.md`: how to run the MySQL/PostgreSQL/SQL Server verification harness locally.
- `InstallationWizard.md`: first-run installer flow, what it configures, and post-install expectations.
- `OperationsGuide.md`: health endpoints, audit logging, console operations, trusted-device behavior, and local backend verification.
- `PaymentDrivers.md`: first-party payment-driver matrix, supported method/flow/webhook taxonomy, and live-mode notes.
- `PresentationTemplating.md`: canonical `.vide` template authoring model, directives, and rendering flow.
- `UtilitiesTraitsOverview.md`: practical overview of the reusable trait surface.
- `UtilitiesTraitsReference.md`: generated method-by-method trait reference.
- `NativeToTraitConsistencyAudit.md`: audit of trait adoption across non-trait classes.
- `IteratorManager.md`: focused reference material for the iterator subsystem.

## Historical / Archival Files

These files are still tracked, but they should be treated as historical notes rather than as the authoritative framework documentation:

- `IteratorManager Usage.pdf`
- `IteratorManager Usage.rtf`
- `abstractcryptoclass.rtf`
- `opensslcryptoclass.rtf`
- `sodiumcryptoclass.rtf`
- `Untitled 5.rtf`
- `Untitled 6.rtf`

## Recommended Reading Order

1. `../readme.md`
2. `ArchitectureOverview.md`
3. `FrameworkStatus.md`
4. `FolderStructure.md`
5. `ModulesStructure.md`
6. Subsystem-specific references as needed
