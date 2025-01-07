<?php

namespace App\Utilities\Finders;

use App\Abstracts\Data\Finder;
use App\Contracts\Data\FinderInterface;
use App\Exceptions\Data\FinderException;
use App\Utilities\Traits\Criteria\DirectoryCriteriaTrait;
use App\Utilities\Traits\Sort\DirectorySortTrait;

/**
 * Class DirectoryFinder
 *
 * Extends the `Finder` abstract class to handle advanced searching, filtering,
 * and processing of **directories** in a file system. By default, this class relies
 * on `Finder` to manage iteration, caching, and root directory detection.
 *
 * **Usage of the `find` Method**
 * - Signature: `find(array $criteria = [], ?string $path = null, array $sort = [])`
 *   - `$path` defaults to the internal `root` determined by `Finder`.
 *   - `$criteria` includes filter conditions recognized automatically by the abstract `Finder`.
 *   - `$sort` can define a sorting callback (e.g., `['callback' => 'name']`).
 *
 * **Available Filter Conditions** (from `DirectoryCriteriaTrait`):
 * - `path`: Match partial path substring (e.g., `'path' => '/myDir'`).
 * - `name`: Match directory name (case-sensitive or insensitive).
 * - `type`: Confirm the item is a directory (default `'directory'`).
 * - `owner`: Filter by owner ID.
 * - `group`: Filter by group ID.
 * - `permissions`: Filter by permission bits (e.g., `0755`).
 * - `modifiedTime`: Only directories modified on or after a specific timestamp.
 * - `accessedTime`: Only directories accessed on or after a specific timestamp.
 * - `creationTime`: Directories created on or after a specific timestamp.
 * - `depth`: Enforce a maximum nesting depth.
 * - `symlink`: Check if a directory is actually a symlink.
 * - `executable`: Check if a directory is executable.
 * - `writable`: Check if a directory is writable.
 * - `readable`: Check if a directory is readable.
 * - `patternName`: Regex-based name matching (e.g., `'/^foo/i'`).
 * - `patternPath`: Regex-based path matching (e.g., `'/^\/home\/user/'`).
 *
 * **Available Sort Keys** (from `DirectorySortTrait`):
 * - `name`: Sort by directory name.
 * - `path`: Sort by full directory path.
 * - `modifiedTime`: Sort by last modified time.
 * - `accessedTime`: Sort by last accessed time.
 * - `creationTime`: Sort by creation time.
 * - `permissions`: Sort by permission bits.
 * - `owner`: Sort by owner ID.
 * - `group`: Sort by group ID.
 *
 * **Example Usage:**
 *
 * #### Example 1: Find Directories (No Path, No Criteria)
 * ```php
 * $finder = new DirectoryFinder();
 * $results = $finder->find();
 * // Returns all directories under the root path determined by Finder.
 * ```
 *
 * #### Example 2: Find Directories with Only Criteria (No Path)
 * ```php
 * $finder = new DirectoryFinder();
 * $criteria = ['name' => 'Projects'];  // Single condition
 * $results = $finder->find($criteria);
 * // Finds directories named "Projects" in the root path.
 *
 * // Another single condition example:
 * $criteria2 = ['writable' => true];
 * $results2 = $finder->find($criteria2);
 * // Finds writable directories under the root.
 *
 * // Multiple conditions:
 * $criteria3 = [
 *     'readable'     => true,
 *     'permissions'  => 0755
 * ];
 * $results3 = $finder->find($criteria3);
 * // Directories that are readable and have 0755 perms under the root.
 * ```
 *
 * #### Example 3: Find Directories with a Custom Path
 * ```php
 * $finder = new DirectoryFinder();
 * $results = $finder->find([], '/var/www');
 * // Lists directories under /var/www with no filters or sorting.
 * ```
 *
 * #### Example 4: Single Criterion with Custom Path
 * ```php
 * $finder = new DirectoryFinder();
 * $criteria = ['name' => 'MyProject'];  // Filter by directory name
 * $results = $finder->find($criteria, '/var/www');
 * // Finds directories named "MyProject" in /var/www.
 * ```
 *
 * #### Example 5: Multiple Criteria + Sorting
 * ```php
 * $finder = new DirectoryFinder();
 * $criteria = [
 *     'writable'     => true,
 *     'permissions'  => 0755,
 *     'patternName'  => '/^demo/i'
 * ];
 * $sortOptions = [
 *     'callback' => 'name'
 * ];
 * $results = $finder->find($criteria, '/home/projects', $sortOptions);
 * // Finds directories that are writable, have perms 0755, name starts with "demo",
 * // then sorts them by directory name.
 * ```
 *
 * #### Example 6: Finding Directories in Multiple Paths
 * ```php
 * $finder = new DirectoryFinder();
 * $criteria = ['executable' => true];
 * $sortOptions = ['callback' => 'path'];
 * $results = $finder->search($criteria, '/usr/local', $sortOptions);
 * // Looks for directories that are executable under /usr/local,
 * // sorted by path, searching across appended subdirectories.
 * ```
 *
 * #### Example 7: Scan a Directory for Basic Info
 * ```php
 * $finder = new DirectoryFinder();
 * $scanData = $finder->scan('/home/user/Documents');
 * // Returns file/directory info for items in /home/user/Documents.
 * ```
 *
 * #### Example 8: Depth-Controlled Directory Search
 * ```php
 * $finder = new DirectoryFinder();
 * $criteria = [
 *     'modifiedTime' => strtotime('-7 days')
 * ];
 * $depth = 2;
 * $results = $finder->findByDepth($criteria, '/srv/storage', $depth);
 * // Lists directories in /srv/storage up to 2 levels deep,
 * // last modified in the last 7 days.
 * ```
 *
 * #### Example 9: Regex-Based Directory Search
 * ```php
 * $finder = new DirectoryFinder();
 * $criteria = ['pattern' => '/^config/i'];
 * $results = $finder->findByRegEx($criteria, '/opt/apps');
 * // Searches for directories named like "config", "configProd", etc., in /opt/apps.
 * ```
 *
 * #### Example 10: Cache-Based Directory Filter
 * ```php
 * // If caching is enabled in Finder:
 * $finder = new DirectoryFinder();
 * $criteria = ['readable' => true];
 * $results = $finder->findByCache($criteria, '/var/logs');
 * // Filter from cached data for directories that are readable, in /var/logs.
 * ```
 */
class DirectoryFinder extends Finder implements FinderInterface
{
	use DirectoryCriteriaTrait, DirectorySortTrait;

	/**
	 * Finds directories based on the given criteria and sorting options.
	 *
	 * @param array $criteria Arbitrary conditions recognized by Finder logic.
	 * @param string|null $path Starting path (default: root).
	 * @param array $sort Sorting options recognized by Finder logic.
	 * @return array Filtered and sorted directory results.
	 *
	 * @throws FinderException If an error occurs during execution.
	 */
	public function find(array $criteria = [], ?string $path = null, array $sort = []): array
	{
		return $this->wrapInTry(
			fn() => $this->handle($criteria, $path, $sort),
			"Error in DirectoryFinder find"
		);
	}

	/**
	 * Searches directories across multiple paths with optional criteria and sorting.
	 *
	 * @param array $criteria Arbitrary conditions recognized by Finder logic.
	 * @param string|null $path Starting path (default: root).
	 * @param array $sort Sorting options recognized by Finder logic.
	 * @return array Matched directories.
	 *
	 * @throws FinderException If an error occurs during execution.
	 */
	public function search(array $criteria = [], ?string $path = null, array $sort = []): array
	{
		return $this->wrapInTry(
			fn() => $this->searchMultipleDirectories([$this->validatePath($path ?? $this->root)], $criteria, $sort),
			"Error during searchDirs"
		);
	}

	/**
	 * Scans a directory and retrieves basic information about its contents.
	 *
	 * @param string|null $path Directory path to scan (default: root).
	 * @return array List of files and directories with details.
	 *
	 * @throws FinderException If the scan fails.
	 */
	public function scan(?string $path = null): array
	{
		return $this->wrapInTry(
			fn() => $this->map(
				fn($item) => $this->getFileInfo($item, $path),
				scandir($this->validatePath($path ?? $this->root)) ?: throw new FinderException("Failed to scan directory")
			),
			"Error during scandir"
		);
	}

	/**
	 * Displays a tree structure of the specified directory.
	 *
	 * @param string|null $path Root directory path to display (default: root).
	 *
	 * @throws FinderException If an error occurs while displaying the tree.
	 */
	public function showTree(?string $path = null): void
	{
		$this->wrapInTry(
			fn() => $this->displayDirectoryTree($this->validatePath($path ?? $this->root)),
			"Error displaying directory tree"
		);
	}

	/**
	 * Filters directories up to a specified depth level.
	 *
	 * @param array $criteria Arbitrary conditions recognized by the Finder logic.
	 * @param string|null $path Starting directory path (default: root).
	 * @param int $maxDepth Maximum depth for filtering.
	 * @param array $sort Sorting options recognized by Finder logic.
	 * @return array Filtered directories.
	 *
	 * @throws FinderException If an error occurs during filtering.
	 */
	public function findByDepth(array $criteria, ?string $path, int $maxDepth = 0, array $sort = []): array
	{
		return $this->wrapInTry(
			fn() => $this->filterWithDepthControl($criteria, $this->validatePath($path ?? $this->root), $maxDepth, $sort),
			"Error filtering directories by depth"
		);
	}

	/**
	 * Filters directories from cache based on the specified criteria.
	 *
	 * @param array $criteria Arbitrary conditions recognized by the Finder logic.
	 * @param string|null $path Starting path (default: root).
	 * @return array Filtered directories from cache.
	 *
	 * @throws FinderException If an error occurs during filtering.
	 */
	public function findByCache(array $criteria = [], ?string $path = null): array
	{
		return $this->wrapInTry(
			fn() => $this->filterWithCache($criteria, $this->validatePath($path ?? $this->root)),
			"Error during cacheDirs"
		);
	}

	/**
	 * Filters directories using a regex pattern.
	 *
	 * @param array $criteria Criteria including a `'pattern'` key for regex matching.
	 * @param string|null $path Starting directory path (default: root).
	 * @return array Matched directories.
	 *
	 * @throws FinderException If an error occurs during regex filtering.
	 */
	public function findByRegEx(array $criteria = [], ?string $path = null): array
	{
		return $this->wrapInTry(
			fn() => $this->filterWithRegex($criteria, $this->validatePath($path ?? $this->root)),
			"Error during regexDirs"
		);
	}

	/**
	 * Retrieves detailed information about a directory or file item.
	 *
	 * @param string $item Name of the file/directory.
	 * @param string|null $path Parent directory path.
	 * @return array File or directory details.
	 *
	 * @throws FinderException If an error occurs while retrieving file information.
	 */
	protected function getFileInfo(string $item, ?string $path): array
	{
		return $this->wrapInTry(function () use ($item, $path) {
			$fileInfo = $this->iteratorManager->FileInfo(
				$this->validatePath($path ?? $this->root) . DIRECTORY_SEPARATOR . $item
			);

			return [
				'name'         => $item,
				'type'         => $fileInfo->isDir() ? 'directory' : 'file',
				'realPath'     => $fileInfo->getRealPath(),
				'size'         => $fileInfo->getSize(),
				'permissions'  => $fileInfo->getPerms(),
				'lastModified' => date("F d Y H:i:s", $fileInfo->getMTime()),
			];
		}, "Error retrieving file info");
	}
}
