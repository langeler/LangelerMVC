# Presentation Layer Evaluation

This note records the current production-readiness evaluation for LangelerMVC presentation, view, template, theme, asset, and resource subsystems.

## Current Position

The presentation layer is now a coherent framework subsystem rather than scattered view helpers:

- `App\Abstracts\Presentation\View` resolves layouts, pages, partials, components, globals, rendering, and template fallback behavior.
- `App\Utilities\Managers\Presentation\TemplateEngine` compiles native `.vide` / `.lmv` templates into cached PHP render targets.
- `App\Utilities\Managers\Presentation\AssetManager` resolves source/public assets, versioned URLs, preload tags, asset bundles, generated tags, and synchronization checks.
- `App\Utilities\Managers\Presentation\HtmlManager` centralizes escaped attributes, conditional class lists, CSRF/method fields, and safe JSON output.
- `App\Utilities\Managers\Presentation\ThemeManager` resolves configured themes, mode policy, public assets, and layout globals.
- `App\Abstracts\Presentation\Resource` and `ResourceCollection` provide JSON/resource payload primitives for controller negotiation.

This aligns with the repo goals of OOP, SRP, SoC, typed boundaries, modularity, and plug-and-play production posture.

## Comparison With Mature PHP Frameworks

This evaluation was checked against current official documentation for:

- Laravel Blade and Vite asset integration: <https://laravel.com/docs/12.x/blade> and <https://laravel.com/docs/12.x/vite>
- Symfony/Twig templates and template components: <https://symfony.com/doc/current/templates.html> and <https://symfony.com/doc/current/components/ux_twig_component.html>
- Twig template language: <https://twig.symfony.com/doc/3.x/templates.html>
- Yii asset bundles: <https://www.yiiframework.com/doc/guide/2.0/en/structure-assets>
- CodeIgniter view layouts: <https://codeigniter.com/user_guide/outgoing/view_layouts.html>

Common mature-framework strengths:

- cached compiled templates
- escaped output by default
- partial/component inclusion
- layout composition
- CSRF and HTTP method helpers
- asset URL helpers, asset versioning, or bundling
- resource-oriented JSON responses
- documented extension seams

LangelerMVC now covers those foundations in its own native style, with the deliberate difference that `.vide` is intentionally constrained toward framework-managed directives and first-party module parity rather than becoming a full PHP-in-template playground.

## What Makes LangelerMVC Distinct

LangelerMVC is not trying to beat Laravel or Symfony by being larger. Its distinct value is a narrower but more integrated source framework:

- Native `.vide` templates are part of the framework identity rather than an imported template language.
- The first-party templates are release-tested to avoid raw PHP tags, which encourages clearer separation between display intent and runtime logic.
- Presentation is integrated with the same module, installer, admin, commerce, payment, shipping, audit, health, and release-gate story as the backend.
- The framework ships a coherent ecommerce/operator baseline instead of leaving every project to assemble auth, admin, cart, orders, payments, carrier tracking, promotions, subscriptions, and documents from separate packages.
- Theme assets are source-tracked under `App/Resources`, mirrored to `Public/assets`, and verified by tests/release checks.
- Provider credentials remain deployment-specific while reference-mode payment/shipping behavior stays deterministic for local development and testing.

## Remaining Advanced Gaps

These are not release blockers, but they are the clearest future improvements if the presentation layer should compete more directly with larger ecosystems:

- A formal component class API with typed props/slots instead of only template-level `@component(...)` includes.
- Asset manifest support for external build tools such as Vite, esbuild, or Rollup.
- Per-module template namespace overrides so modules/packages can provide overrideable templates without copying shared files.
- Fragment caching directives for expensive partials/components.
- Development diagnostics for template compile errors with source-line mapping.
- Browser/a11y regression automation beyond static template heuristics.

## Implemented In This Pass

This pass closed the highest-leverage production ergonomics gaps without destabilizing modules:

- Added `HtmlManagerInterface` and `HtmlManager` as a dedicated SRP boundary for safe HTML output.
- Added the `html` core service.
- Extended `AssetManagerInterface` and `AssetManager` with cache-busted URLs, preloads, and named bundles.
- Added `.vide` directives: `@assetVersion`, `@assetBundle`, `@preload`, `@attr`, `@attrs`, and `@json`.
- Updated first-party `.vide` layouts to render theme CSS/JS with versioned URLs.
- Added regression coverage for the new helper boundaries.
- Added `.vide` section/yield/stack directives so layouts can expose named regions while the existing `$content` flow stays compatible.

## Priority Recommendation

The best next presentation milestone is **typed component props/slots plus optional asset manifest support**.

That would bring `.vide` closer to the mature ergonomics developers expect from Blade/Twig while preserving the framework's unique native, directive-first identity. The framework-wide competitive context is tracked in `Docs/FrameworkWideLayerEvaluation.md`.
