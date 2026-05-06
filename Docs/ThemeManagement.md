# Theme Management

LangelerMVC ships a framework-wide theme subsystem so new applications can start with a coherent, production-ready UI without hand-editing every first-party layout.

## Release Defaults

- Default theme: `bootstrap-light`
- Default mode: `system`
- Public CSS: `/assets/css/langelermvc-theme.css`
- Public JavaScript: `/assets/js/langelermvc-theme.js`
- Source CSS: `App/Resources/css/langelermvc-theme.css`
- Source JavaScript: `App/Resources/js/langelermvc-theme.js`

The first-party catalog includes:

- `bootstrap-light`: professional light Bootstrap-compatible token set.
- `bootstrap-dark`: professional dark Bootstrap-compatible token set.
- `bootstrap-system`: system-preference mode using the same token contract.

## Environment Keys

```dotenv
THEME_DEFAULT=bootstrap-light
THEME_MODE=system
THEME_ALLOW_USER_SELECTION=true
THEME_ASSET_CSS=/assets/css/langelermvc-theme.css
THEME_ASSET_JS=/assets/js/langelermvc-theme.js
```

`THEME_DEFAULT` chooses the named framework theme. `THEME_MODE` controls the initial color mode and accepts `light`, `dark`, or `system`.

## Runtime Integration

`App\Utilities\Managers\Presentation\ThemeManager` resolves the configured catalog, active theme, public assets, storage key, cookie name, and layout globals. It is available from the core provider as the `themes` service.

`App\Utilities\Managers\Presentation\AssetManager` owns source/public asset resolution, generated `@style`, `@script`, `@image`, `@preload`, and `@assetBundle` output, versioned public URLs, and source/public synchronization reporting. It is available from the core provider as the `assets` service.

`App\Utilities\Managers\Presentation\HtmlManager` owns escaped HTML attributes, conditional classes, CSRF/method fields, and safe JSON output. It is available from the core provider as the `html` service.

`App\Support\Theming\ThemeManager` remains as a thin backward-compatible alias for older projects, but new code should use the canonical manager namespace above.

All first-party views share theme globals into their layouts:

- installer
- public WebModule pages
- UserModule identity screens
- ShopModule, CartModule, and OrderModule surfaces
- AdminModule operations surfaces

## Asset Contract

The release-tracked public assets are intentionally small and dependency-light while exposing Bootstrap-compatible design tokens and common component classes such as `.container`, `.row`, `.col`, `.card`, `.btn`, `.btn-primary`, `.form-control`, `.form-select`, `.table`, `.alert`, and `.badge`.

First-party layouts render theme assets with versioned URLs, so browser caches refresh when the tracked CSS/JS files change while custom external asset URLs remain untouched.

When changing the source files, keep the public copies synchronized:

```bash
cp App/Resources/css/langelermvc-theme.css Public/assets/css/langelermvc-theme.css
cp App/Resources/js/langelermvc-theme.js Public/assets/js/langelermvc-theme.js
```

You can verify synchronization programmatically through the `assets` service:

```php
$report = $provider->getCoreService('assets')->synchronizationReport();
```

## Accessibility Notes

The theme bundle includes:

- visible focus treatment for links, controls, and buttons
- `prefers-color-scheme` support for system mode
- `prefers-reduced-motion` handling
- an accessible `data-theme-toggle` control with `aria-label`, `aria-pressed`, and a visible label

The release check validates that required theme config, source assets, public assets, and `.env.example` keys are present.
