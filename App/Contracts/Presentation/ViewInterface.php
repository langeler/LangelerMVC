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
	 * Render a layout template with given data.
	 *
	 * @param string $layout The layout name.
	 * @param array<string,mixed> $data Data to pass to the layout.
	 * @return string The rendered layout output.
	 */
	public function renderLayout(string $layout, array $data = []): string;

	/**
	 * Render a page template with given data.
	 *
	 * @param string $page The page name.
	 * @param array<string,mixed> $data Data to pass to the page.
	 * @return string The rendered page output.
	 */
	public function renderPage(string $page, array $data = []): string;

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
	 * Render an asset (e.g., CSS, JS).
	 *
	 * @param string $type  The asset type (css, js, etc.).
	 * @param string $asset The asset name.
	 * @return string The resolved asset path or URL.
	 */
	public function renderAsset(string $type, string $asset): string;

	/**
	 * Set global variables available to all templates.
	 *
	 * @param array<string,mixed> $variables The global variables to set.
	 * @return void
	 */
	public function setGlobals(array $variables): void;

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
