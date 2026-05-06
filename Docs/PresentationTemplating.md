# Presentation Templating

LangelerMVC now treats presentation as a first-class framework layer, not as raw PHP views with light helpers.

The canonical template format is `.vide`.

## Goals

The native templating surface is designed to align with the framework design goals:

- modular shared layouts, pages, partials, and components
- readable source templates with explicit directives instead of embedded PHP blocks
- maintainable presentation code with less mixed rendering/runtime logic
- compatibility with the existing view abstraction and layout pipeline
- framework-wide theme globals for light/dark/system presentation without module-local styling forks
- backward-readable `.lmv` and `.php` template fallbacks without making them the primary authoring surface

## Template Structure

Shared templates live under `App/Templates`:

- `Layouts/`: frame-level wrappers such as `WebShell.vide`, `UserShell.vide`, `AdminShell.vide`, and `InstallerShell.vide`
- `Pages/`: concrete page templates for first-party module screens
- `Partials/`: reusable fragments such as `PageIntro.vide` and `StatusMessage.vide`
- `Components/`: reusable UI/data primitives such as `DataTable.vide`, `DefinitionGrid.vide`, and `ProductGrid.vide`

Concrete module views render those templates through `App\Abstracts\Presentation\View`.

First-party layouts also consume shared theme globals from `App\Utilities\Managers\Presentation\ThemeManager`, including `themeName`, `themeMode`, `themeAssetCss`, `themeAssetJs`, and `themeToggleEnabled`.

Presentation managers are intentionally grouped under `App\Utilities\Managers\Presentation`:

- `TemplateEngine`: compiles native `.vide` / `.lmv` templates into cached PHP render targets.
- `ThemeManager`: resolves configured themes and shared layout globals.
- `AssetManager`: resolves source assets, public asset URLs, generated HTML tags, and source/public synchronization checks.
- `HtmlManager`: owns escaped attributes, conditional class lists, CSRF/method hidden fields, and script-safe JSON output.

## Authoring Rules

Canonical `.vide` templates should:

- use native directives rather than raw `<?php` / `<?=`
- keep display logic close to presentation concerns only
- prefer `@include(...)` and `@component(...)` over duplicated markup
- use view helpers such as `{{ ... }}` and `{!! ... !!}` rather than inline escaping logic

The framework regression suite now enforces that native `.vide` source files do not contain raw PHP tags.

## Supported Directives

The native compiler currently supports:

- `@include(...)`
- `@component(...)`
- `@asset(...)`
- `@assetUrl(...)`
- `@assetVersion(...)`
- `@assetBundle(...)`
- `@preload(...)`
- `@style(...)`
- `@script(...)`
- `@image(...)`
- `@csrf`
- `@method(...)`
- `@class(...)`
- `@attr(...)` / `@attrs(...)`
- `@json(...)`
- `@php ... @endphp`
- `@if(...)`, `@elseif(...)`, `@else`, `@endif`
- `@unless(...)`, `@endunless`
- `@isset(...)`, `@endisset`
- `@empty(...)`, `@endempty`
- `@foreach(...)`, `@endforeach`
- `@for(...)`, `@endfor`
- `@while(...)`, `@endwhile`
- `@checked(...)`
- `@selected(...)`
- `@disabled(...)`
- `@readonly(...)`
- `@required(...)`

Output helpers:

- `{{ ... }}`: escaped output through the view layer
- `{!! ... !!}`: raw output when the content is already trusted/rendered

## Rendering Flow

At runtime:

1. `View` resolves the requested layout/page/partial/component path.
2. `TemplateEngine` detects `.vide` input and compiles it into cached PHP under `Storage/Cache/Templates`.
3. `AssetManager` resolves source asset paths, public URLs, and tag helpers exposed through native template directives.
4. `HtmlManager` handles escaped attributes, class lists, CSRF/method fields, and safe JSON output so templates do not reimplement low-level HTML concerns.
5. `View` renders the compiled template with shared globals and page-local data.
6. Layout composition happens through the same `View` abstraction rather than template-side inheritance magic.

This keeps template compilation and rendering inside framework-native boundaries.

## Compatibility

`.vide` is the canonical authoring format.

`.lmv` and `.php` templates remain readable as compatibility fallbacks for older templates or staged transitions, but new first-party work should target `.vide` first.

## First-Party Usage

All first-party shared templates now use `.vide` as the source-of-truth format across:

- `WebModule`
- `UserModule`
- `AdminModule`
- `ShopModule`
- `CartModule`
- `OrderModule`
- the installer flow

That means the reference application surface exercises the same native templating API the framework expects downstream applications to use.

## Production Asset Helpers

The `assets` service supports local/public URL resolution, cache-busted URLs, preload tags, and named bundles.

```php
$assets = $provider->getCoreService('assets');
$assets->versionedUrl('css', 'langelermvc-theme.css');
$assets->preloadTag('css', 'langelermvc-theme.css', ['versioned' => true]);
$assets->bundleTags('framework-theme');
```

The matching `.vide` helpers are:

```blade
@assetVersion("css", "langelermvc-theme.css")
@preload("css", "langelermvc-theme.css", ["versioned" => true])
@assetBundle("framework-theme")
```
