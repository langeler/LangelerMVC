# Presentation Managers

This is the canonical manager layer for framework presentation orchestration.

Presentation managers keep resources, themes, assets, and native templates developer-friendly without forcing module controllers to know about filesystem details.

Manager responsibilities:

- `AssetManager`: resolves source assets, public URLs, generated HTML tags, and source/public synchronization checks.
- `HtmlManager`: owns escaped attributes, conditional classes, CSRF/method fields, and safe JSON output for templates.
- `TemplateEngine`: compiles `.vide` and `.lmv` templates into cached PHP render targets, including native section/yield/stack layout composition directives.
- `ThemeManager`: resolves configured themes, mode defaults, public assets, and shared layout globals.

First-party templates should prefer `.vide` directives such as `@section`, `@yield`, `@push`, `@stack`, `@style`, `@script`, `@image`, `@preload`, `@assetBundle`, `@csrf`, `@method`, `@class`, `@attr`, and `@json` instead of raw PHP tags. `App/Support/Theming/ThemeManager.php` remains as a backward-compatible alias for older projects.
