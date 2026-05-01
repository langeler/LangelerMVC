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

`App\Support\Theming\ThemeManager` resolves the configured catalog, active theme, public assets, storage key, cookie name, and layout globals. It is available from the core provider as the `themes` service.

All first-party views share theme globals into their layouts:

- installer
- public WebModule pages
- UserModule identity screens
- ShopModule, CartModule, and OrderModule surfaces
- AdminModule operations surfaces

## Asset Contract

The release-tracked public assets are intentionally small and dependency-light while exposing Bootstrap-compatible design tokens and common component classes such as `.container`, `.row`, `.col`, `.card`, `.btn`, `.btn-primary`, `.form-control`, `.form-select`, `.table`, `.alert`, and `.badge`.

When changing the source files, keep the public copies synchronized:

```bash
cp App/Resources/css/langelermvc-theme.css Public/assets/css/langelermvc-theme.css
cp App/Resources/js/langelermvc-theme.js Public/assets/js/langelermvc-theme.js
```

## Accessibility Notes

The theme bundle includes:

- visible focus treatment for links, controls, and buttons
- `prefers-color-scheme` support for system mode
- `prefers-reduced-motion` handling
- an accessible `data-theme-toggle` control with `aria-label`, `aria-pressed`, and a visible label

The release check validates that required theme config, source assets, public assets, and `.env.example` keys are present.
