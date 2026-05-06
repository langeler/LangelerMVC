# Documentation Index

This folder contains the current LangelerMVC project documentation plus a small set of historical reference files kept in the repository for context.

As of `2026-05-01`, the framework status, release readiness, and architecture documents below are the primary source of truth for what is implemented today.

## Start Here

- `../readme.md`: project overview, installation, run commands, and quick links into the documentation set.
- `../CONTRIBUTING.md`: contributor workflow, verification expectations, and coding standards for framework work.
- `../SECURITY.md`: supported versions and responsible vulnerability disclosure guidance.
- `../CHANGELOG.md`: release-facing change history.
- `../RELEASE.md`: published `v1.0.0` snapshot, future release checks, and production deployment preflight.
- `RepositoryMetadata.md`: canonical repository/package description, topics, about text, and release-publication posture.
- `ArchitectureOverview.md`: framework architecture, layer responsibilities, request lifecycle, subsystem map, and extension points.
- `DeploymentAndUpgrade.md`: production deployment, upgrade, rollback, worker, and smoke-test recipes.
- `FrameworkStatus.md`: current implementation status, verified coverage snapshot, and remaining environment-dependent hardening areas.
- `ReleaseReadinessPlan.md`: published release posture and deployment-specific hardening map.
- `Wiki/`: versioned source pages for the GitHub Wiki. Push these to `LangelerMVC.wiki.git` after GitHub initializes the first wiki page.

## Structure And Layout

- `FolderStructure.md`: the current repository layout by layer and responsibility.
- `ModulesStructure.md`: module conventions, module loading, and the current state of each module.
- `CompleteStructure.md`: a full repository tree snapshot kept for visual orientation.
- `Wiki/`: public-facing wiki page source mirrored from the release docs.
- `../Data/README.md`: release-reference SQL snapshot notes for the grouped `Data/*.sql` files.

## Subsystem And Reference Docs

- `SanitationValidationAPI.md`: current schema contract for sanitizers and validators.
- `DatabaseMatrixTesting.md`: how to run the MySQL/PostgreSQL/SQL Server verification harness locally.
- `DeploymentAndUpgrade.md`: production deployment and upgrade recipes.
- `InstallationWizard.md`: first-run installer flow, what it configures, and post-install expectations.
- `ThemeManagement.md`: framework-wide Bootstrap-compatible light/dark/system theme management.
- `OperationsGuide.md`: health endpoints, audit logging, console operations, trusted-device behavior, and local backend verification.
- `PaymentDrivers.md`: first-party payment-driver matrix, supported method/flow/webhook taxonomy, and live-mode notes.
- `ShippingAdapters.md`: first-party carrier adapter registry, Swedish carrier matrix, reference/live mode, and extension pattern.
- `PresentationTemplating.md`: canonical `.vide` template authoring model, directives, and rendering flow.
- `PresentationLayerEvaluation.md`: presentation-layer gap analysis, mature-framework comparison, differentiators, and next priorities.
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
4. `ReleaseReadinessPlan.md`
5. `DeploymentAndUpgrade.md`
6. `FolderStructure.md`
7. `ModulesStructure.md`
8. Subsystem-specific references as needed
