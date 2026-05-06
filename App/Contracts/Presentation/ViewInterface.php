<?php

declare(strict_types=1);

namespace App\Contracts\Presentation;

/**
 * ViewInterface
 *
 * Defines the contract for rendering templates and managing cached templates,
 * globals, and resolved paths. Aligns with the abstract View class.
 */
interface ViewInterface
{
	/**
	 * Set the default layout used when rendering pages.
	 *
	 * @param string $layout
	 * @return static
	 */
	public function setDefaultLayout(string $layout): static;

	/**
	 * Get the current default layout, if any.
	 *
	 * @return string|null
	 */
	public function getDefaultLayout(): ?string;

	/**
	 * Clear the current default layout.
	 *
	 * @return static
	 */
	public function clearDefaultLayout(): static;

	/**
	 * Render a layout template with given data.
	 *
	 * @param string $layout The layout name.
	 * @param array<string,mixed> $data Data to pass to the layout.
	 * @return string The rendered layout output.
	 */
	public function renderLayout(string $layout, array $data = []): string;

	/**
	 * Render a page template with the active default layout when configured.
	 *
	 * @param string $page The page name.
	 * @param array<string,mixed> $data Data to pass to the page.
	 * @return string The rendered page output.
	 */
	public function renderPage(string $page, array $data = []): string;

	/**
	 * Render a page template without applying a layout.
	 *
	 * @param string $page
	 * @param array<string,mixed> $data
	 * @return string
	 */
	public function renderPageContent(string $page, array $data = []): string;

	/**
	 * Render a page template inside a specific layout.
	 *
	 * @param string $layout
	 * @param string $page
	 * @param array<string,mixed> $data
	 * @return string
	 */
	public function renderPageWithLayout(string $layout, string $page, array $data = []): string;

	/**
	 * Render a partial template.
	 *
	 * @param string $partial The partial name.
	 * @param array<string,mixed> $data Data to pass to the partial.
	 * @return string The rendered partial output.
	 */
	public function renderPartial(string $partial, array $data = []): string;

	/**
	 * Render a component template.
	 *
	 * @param string $component The component name.
	 * @param array<string,mixed> $data Data to pass to the component.
	 * @return string The rendered component output.
	 */
	public function renderComponent(string $component, array $data = []): string;

	/**
	 * Determine whether a template exists within the presentation tree.
	 *
	 * @param string $type One of layout, page, partial, or component.
	 * @param string $template
	 * @return bool
	 */
	public function templateExists(string $type, string $template): bool;

	/**
	 * Render an asset (e.g., CSS, JS).
	 *
	 * @param string $type  The asset type (css, js, etc.).
	 * @param string $asset The asset name.
	 * @return string The resolved asset path or URL.
	 */
	public function renderAsset(string $type, string $asset): string;

	/**
	 * Resolve a public asset URL for use inside rendered HTML.
	 *
	 * @param string $type
	 * @param string $asset
	 * @return string
	 */
	public function assetUrl(string $type, string $asset): string;

	/**
	 * Resolve a cache-busted public asset URL.
	 *
	 * @param string $type
	 * @param string $asset
	 * @return string
	 */
	public function assetVersion(string $type, string $asset): string;

	/**
	 * Render a stylesheet tag using the framework asset contract.
	 *
	 * @param string $asset
	 * @param array<string,mixed> $attributes
	 * @return string
	 */
	public function styleTag(string $asset, array $attributes = []): string;

	/**
	 * Render a script tag using the framework asset contract.
	 *
	 * @param string $asset
	 * @param array<string,mixed> $attributes
	 * @return string
	 */
	public function scriptTag(string $asset, array $attributes = []): string;

	/**
	 * Render an image tag using the framework asset contract.
	 *
	 * @param string $asset
	 * @param string $alt
	 * @param array<string,mixed> $attributes
	 * @return string
	 */
	public function imageTag(string $asset, string $alt = '', array $attributes = []): string;

	/**
	 * Render a preload tag using the framework asset contract.
	 *
	 * @param string $type
	 * @param string $asset
	 * @param array<string,mixed> $attributes
	 * @return string
	 */
	public function preloadTag(string $type, string $asset, array $attributes = []): string;

	/**
	 * Render all tags for a named asset bundle.
	 *
	 * @param string $name
	 * @param array<string,mixed> $attributes
	 * @return string
	 */
	public function assetBundle(string $name, array $attributes = []): string;

	/**
	 * Render a CSRF hidden field when a token has been shared with the view.
	 *
	 * @return string
	 */
	public function csrfField(): string;

	/**
	 * Render an HTTP method override hidden field for non-GET/POST forms.
	 *
	 * @param string $method
	 * @return string
	 */
	public function formMethod(string $method): string;

	/**
	 * Build a conditional class string from a list or keyed conditions.
	 *
	 * @param array<int|string,mixed>|string $classes
	 * @return string
	 */
	public function classList(array|string $classes): string;

	/**
	 * Build escaped HTML attributes.
	 *
	 * @param array<string,mixed> $attributes
	 * @return string
	 */
	public function attributes(array $attributes): string;

	/**
	 * Render JSON that is safe to embed inside HTML script contexts.
	 *
	 * @param mixed $value
	 * @param int $flags
	 * @param int $depth
	 * @return string
	 */
	public function jsonForScript(mixed $value, int $flags = 0, int $depth = 512): string;

	/**
	 * Set global variables available to all templates.
	 *
	 * @param array<string,mixed> $variables The global variables to set.
	 * @return void
	 */
	public function setGlobals(array $variables): void;

	/**
	 * Share one or more global variables with the view layer.
	 *
	 * @param string|array<string,mixed> $key
	 * @param mixed $value
	 * @return static
	 */
	public function share(string|array $key, mixed $value = null): static;

	/**
	 * Retrieve all global variables.
	 *
	 * @return array<string,mixed> The global variables.
	 */
	public function getGlobals(): array;

	/**
	 * Cache a template output with an optional TTL.
	 *
	 * @param string   $key     The cache key.
	 * @param string   $content The template content.
	 * @param int|null $ttl     Time to live in seconds.
	 * @return void
	 */
	public function cacheTemplate(string $key, string $content, ?int $ttl = null): void;

	/**
	 * Fetch a cached template by key.
	 *
	 * @param string $key The cache key.
	 * @return string|null The cached template content or null if not found.
	 */
	public function fetchCachedTemplate(string $key): ?string;
}
