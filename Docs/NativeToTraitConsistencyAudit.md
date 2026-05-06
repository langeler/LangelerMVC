# Native PHP To Trait Consistency Audit

This document summarizes the current native-PHP-to-framework-trait audit for classes under `App/`.

The audit is intentionally advisory. It highlights direct native PHP calls that have a framework trait wrapper somewhere in the codebase, but not every candidate should be blindly replaced. Low-level adapters, generated-template targets, provider integrations, and concise domain logic can still reasonably use native PHP when adding a trait would make the class less clear.

To regenerate the full per-class report:

```bash
perl Scripts/AuditNativeToTraitConsistency.pl . > /tmp/langelermvc-native-trait-audit.md
```

## Current Snapshot

Latest local audit after the presentation/HTML helper and commerce manager relocation:

- Class files scanned: `335`
- Class files with at least one replacement candidate: `134`
- Total native-call occurrences matching existing trait wrappers: `2003`
- Low-friction replacement paths (`already-composed`): `257`
- Structural replacement paths (`available-via-trait`): `2013`

## High-Value Interpretation

- Presentation manager code now leans further into framework-native traits: `TemplateEngine`, `ThemeManager`, and `AssetManager` use shared array, string, pattern, hashing, type-checking, path, and encoding helpers where they add clarity.
- `HtmlManager` now owns low-level safe HTML output using framework traits instead of leaving CSRF fields, method fields, conditional classes, attributes, and script-safe JSON as ad hoc view logic.
- Commerce operational managers now live under `App/Utilities/Managers/Commerce`, making future native-to-trait passes easier to target as a focused subsystem instead of scattered support classes.
- The remaining high-count files are mostly deep commerce/admin orchestration classes where a full conversion should be deliberate, not mechanical.
- The best next refactor candidates are classes that already compose the relevant trait and therefore can replace native calls without widening their dependencies.
- Adding traits to domain services should be weighed against readability. The project goal is framework-native consistency, not trait maximalism at the cost of clarity.

## Top Native Calls With Existing Trait Replacements

- `trim`: `395`
- `is_array`: `307`
- `array_map`: `162`
- `array_values`: `155`
- `strtolower`: `139`
- `in_array`: `129`
- `array_filter`: `93`
- `strtoupper`: `78`
- `is_string`: `77`
- `substr`: `41`
- `array_key_exists`: `40`
- `array_unique`: `32`
- `array_keys`: `30`
- `preg_match`: `27`
- `filter_var`: `26`

## Top Classes By Replacement Opportunity

- `App/Modules/AdminModule/Services/AdminAccessService.php`: `169` occurrences
- `App/Utilities/Managers/Commerce/ShippingManager.php`: `145` occurrences
- `App/Utilities/Managers/Commerce/PromotionManager.php`: `126` occurrences
- `App/Modules/OrderModule/Services/OrderService.php`: `98` occurrences
- `App/Installer/InstallerWizard.php`: `95` occurrences
- `App/Console/Commands/ReleaseCheckCommand.php`: `93` occurrences
- `App/Abstracts/Support/CarrierAdapter.php`: `88` occurrences
- `App/Modules/CartModule/Repositories/PromotionRepository.php`: `76` occurrences
- `App/Utilities/Managers/Commerce/SubscriptionManager.php`: `43` occurrences
- `App/Utilities/Managers/Async/QueueManager.php`: `40` occurrences

## Refactor Policy

Prefer framework-native traits when:

- the class already composes the trait that provides the wrapper;
- the replacement makes intent clearer, such as `isArray`, `keyExists`, `replaceByPattern`, or `hashString`;
- the class is framework infrastructure rather than a one-off adapter;
- the replacement keeps error handling, escaping, path normalization, or validation inside existing framework seams.

Keep native PHP when:

- the native call is a language-level construct or a clearer one-liner;
- adding a trait would significantly widen a small class dependency surface;
- the call lives in a provider SDK adapter where vendor terminology is clearer;
- the code is a generated compatibility template target rather than canonical `.vide` source.

## Current Priority

The next sensible native-to-trait improvements are:

- continue with classes already composing low-friction traits, especially queue, doctor, payment manager, and HTTP security code;
- refactor commerce/admin orchestration only in focused passes with regression tests;
- avoid sweeping mechanical rewrites across high-value release code without a behavior test around each touched workflow.
