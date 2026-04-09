<?php

namespace App\Contracts\Data;

/**
 * Interface FinderInterface
 *
 * Defines the contract for file and directory finders.
 * Ensures a consistent structure for searching, filtering, and processing data.
 */
interface FinderInterface
{
	/**
	 * Finds items (files/directories) based on criteria and sorting options.
	 *
	 * @param array $criteria Search criteria for filtering items.
	 * @param string|null $path Starting path (default: root).
	 * @param array $sort Sorting options.
	 * @return array Filtered and sorted results.
	 */
	public function find(array $criteria = [], ?string $path = null, array $sort = []): array;

	/**
	 * Searches items (files/directories) across multiple directories.
	 *
	 * @param array $criteria Search criteria for filtering.
	 * @param string|null $path Starting path (default: root).
	 * @param array $sort Sorting options.
	 * @return array Matched items across multiple directories.
	 */
	public function search(array $criteria = [], string|array|null $path = null, array $sort = []): array;

	/**
	 * Scans a directory and retrieves information about its contents.
	 *
	 * @param string|null $path Directory path to scan (default: root).
	 * @return array List of items (files/directories) with details.
	 */
	public function scan(?string $path = null): array;

	/**
	 * Build and return a hierarchical tree representation of the specified path.
	 *
	 * @param string|null $path Root directory path to render (default: root).
	 * @return string Tree output.
	 */
	public function tree(?string $path = null): string;

	/**
	 * Displays a hierarchical tree structure of the specified path.
	 *
	 * @param string|null $path Root directory path to display (default: root).
	 * @return void
	 */
	public function showTree(?string $path = null): void;

	/**
	 * Return the resolved root path used by the finder.
	 *
	 * @return string
	 */
	public function getRoot(): string;

	/**
	 * Enable or disable implicit caching for iterative finder operations.
	 *
	 * @param bool $enabled
	 * @return static
	 */
	public function useCache(bool $enabled = true): static;

	/**
	 * Clear all cached data or the cache entry for a specific path.
	 *
	 * @param string|null $path
	 * @return static
	 */
	public function clearCache(?string $path = null): static;

	/**
	 * Filters items (files/directories) up to a specified depth level.
	 *
	 * @param array $criteria Search criteria for filtering.
	 * @param string|null $path Starting path (default: root).
	 * @param int $maxDepth Maximum depth level.
	 * @param array $sort Sorting options.
	 * @return array Filtered results within the specified depth.
	 */
	public function findByDepth(array $criteria, ?string $path, int $maxDepth = 0, array $sort = []): array;

	/**
	 * Filters items (files/directories) from cache based on criteria.
	 *
	 * @param array $criteria Search criteria for filtering.
	 * @param string|null $path Starting path (default: root).
	 * @return array Filtered results from cache.
	 */
	public function findByCache(array $criteria = [], ?string $path = null, array $sort = []): array;

	/**
	 * Filters items (files/directories) using a regex pattern.
	 *
	 * @param array $criteria Search criteria including regex patterns.
	 * @param string|null $path Starting path (default: root).
	 * @param array $sort Sorting options.
	 * @return array Filtered results matching the regex.
	 */
	public function findByPattern(array $criteria = [], ?string $path = null, array $sort = []): array;

	/**
	 * Backward-compatible alias of `findByPattern()`.
	 *
	 * @param array $criteria
	 * @param string|null $path
	 * @param array $sort
	 * @return array
	 */
	public function findByRegEx(array $criteria = [], ?string $path = null, array $sort = []): array;
}
