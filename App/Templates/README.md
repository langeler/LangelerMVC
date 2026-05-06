# Templates

Shared presentation templates for first-party modules and installers.

- `.vide` is the canonical authoring format.
- `.lmv` and `.php` files remain compatibility fallbacks.
- Layout, page, partial, component, asset, theme, safe HTML, and helper resolution flows through `App\Utilities\Managers\Presentation`.
- Prefer native directives such as `@include`, `@component`, `@style`, `@script`, `@image`, `@preload`, `@assetBundle`, `@csrf`, `@method`, `@class`, `@attr`, and `@json` instead of embedding raw PHP in `.vide` files.
