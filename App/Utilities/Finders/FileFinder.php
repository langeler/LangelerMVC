<?php

namespace App\Utilities\Finders;

use App\Abstracts\Data\Finder;
use App\Contracts\Data\FinderInterface;
use App\Exceptions\Data\FinderException;
use App\Utilities\Traits\Criteria\FileCriteriaTrait;
use App\Utilities\Traits\Sort\FileSortTrait;

/**
 * Class FileFinder
 *
 * Extends the `Finder` abstract class to handle searching, filtering, and processing
 * of **files** in a file system. By default, this class relies on `Finder` to manage
 * iteration, caching, and root directory detection.
 *
 * **Usage of the `find` Method**
 * - Signature: `find(array $criteria = [], ?string $path = null, array $sort = [])`
 *   - `$path` defaults to the internal `root` determined by `Finder`.
 *   - `$criteria` includes filter conditions automatically recognized by the abstract `Finder`.
 *   - `$sort` can specify a sorting callback (e.g., `['callback' => 'name']`).
 *
 * **Available Filter Conditions** (from `FileCriteriaTrait`):
 * - `path`: Match a partial file path substring.
 * - `name`: Match filename (case-sensitive or insensitive).
 * - `extension`: Match file extension (e.g., `'extension' => 'txt'`).
 * - `size`: Match files that are at least a certain size in bytes.
 * - `permissions`: Filter by file permission bits (e.g., `0755`).
 * - `owner`: Filter by owner ID.
 * - `group`: Filter by group ID.
 * - `modifiedTime`: Files modified on or after a specific timestamp.
 * - `accessedTime`: Files accessed on or after a specific timestamp.
 * - `creationTime`: Files created on or after a specific timestamp.
 * - `symlink`: Check if a file is actually a symbolic link.
 * - `depth`: Maximum directory depth for file searches.
 * - `executable`: Files that are executable.
 * - `writable`: Files that are writable.
 * - `readable`: Files that are readable.
 * - `patternName`: Regex-based name matching (e.g., `'/^report/i'`).
 * - `patternExtension`: Regex-based extension matching.
 * - `patternPath`: Regex-based path matching.
 *
 * **Available Sort Keys** (from `FileSortTrait`):
 * - `name`: Sort by filename.
 * - `path`: Sort by full file path.
 * - `size`: Sort by file size.
 * - `extension`: Sort by file extension.
 * - `modifiedTime`: Sort by last modified time.
 * - `accessedTime`: Sort by last accessed time.
 * - `creationTime`: Sort by creation time.
 * - `permissions`: Sort by file permission bits.
 * - `owner`: Sort by owner ID.
 * - `group`: Sort by group ID.
 *
 * **Example Usage:**
 *
 * #### Example 1: Find Files (No Path, No Criteria)
 * ```php
 * $finder = new FileFinder();
 * $results = $finder->find();
 * // Returns all files under the root path determined by the abstract Finder.
 * ```
 *
 * #### Example 2: Find Files with Only Criteria (No Path)
 * ```php
 * $finder = new FileFinder();
 * $criteria = ['extension' => 'log']; // Single condition
 * $results = $finder->find($criteria);
 * // Finds all ".log" files under the root.
 *
 * // Another single condition example:
 * $criteria2 = ['writable' => true];
 * $results2 = $finder->find($criteria2);
 * // Finds writable files under the root path.
 *
 * // Multiple conditions:
 * $criteria3 = [
 *     'executable'   => true,
 *     'patternName'  => '/^script/i'
 * ];
 * $results3 = $finder->find($criteria3);
 * // Files that are executable and name starts with "script" (case-insensitive).
 * ```
 *
 * #### Example 3: Find Files with a Custom Path
 * ```php
 * $finder = new FileFinder();
 * $results = $finder->find([], '/var/www');
 * // Lists files under /var/www with no filters or sorting.
 * ```
 *
 * #### Example 4: Single Condition with Custom Path
 * ```php
 * $finder = new FileFinder();
 * $criteria = ['name' => 'config.php'];
 * $results = $finder->find($criteria, '/var/www');
 * // Finds files named "config.php" in /var/www.
 * ```
 *
 * #### Example 5: Multiple Conditions + Sorting
 * ```php
 * $finder = new FileFinder();
 * $criteria = [
 *     'extension'   => 'txt',
 *     'size'        => 1000,      // at least 1 KB
 *     'patternName' => '/^report/'
 * ];
 * $sort = [
 *     'callback' => 'size'
 * ];
 * $results = $finder->find($criteria, '/home/user/docs', $sort);
 * // Finds ".txt" files >= 1KB, name begins with "report",
 * // sorted by file size in ascending order.
 * ```
 *
 * #### Example 6: Searching Files in Multiple Directories
 * ```php
 * $finder = new FileFinder();
 * $criteria = ['owner' => 1001]; // files owned by user ID 1001
 * $sort = ['callback' => 'path'];
 * $results = $finder->search($criteria, '/usr/local', $sort);
 * // Combines subdirectories under /usr/local,
 * // filters by owner=1001, sorts by path.
 * ```
 *
 * #### Example 7: Scanning a Directory for File Info
 * ```php
 * $finder = new FileFinder();
 * $scanData = $finder->scan('/home/user/Documents');
 * // Returns basic file info for each item in /home/user/Documents.
 * ```
 *
 * #### Example 8: Depth-Controlled File Search
 * ```php
 * $finder = new FileFinder();
 * $criteria = [
 *     'modifiedTime' => strtotime('-1 week')
 * ];
 * $maxDepth = 2;
 * $results = $finder->findByDepth($criteria, $maxDepth, '/var/logs');
 * // Lists files in /var/logs up to 2 levels deep,
 * // modified within the last week.
 * ```
 *
 * #### Example 9: Cache-Based File Filtering
 * ```php
 * // If caching is enabled in Finder:
 * $finder = new FileFinder();
 * $criteria = ['readable' => true];
 * $results = $finder->findByCache($criteria, '/var/cache');
 * // Filters from cached data, returning only readable files under /var/cache.
 * ```
 *
 * #### Example 10: Regex-Based File Search
 * ```php
 * $finder = new FileFinder();
 * $criteria = ['pattern' => '/\.bak$/i']; // matches ".bak" extension
 * $results = $finder->findByRegEx($criteria, '/opt/projects');
 * // Finds ".bak" files in /opt/projects, ignoring case.
 * ```
 */
class FileFinder extends Finder implements FinderInterface
{
    use FileCriteriaTrait, FileSortTrait;

    /**
     * Finds files based on given criteria and sorting options.
     *
     * @param array $criteria Arbitrary conditions recognized by the Finder logic.
     * @param string|null $path Directory path to search (default: root).
     * @param array $sort Sorting options recognized by the Finder logic.
     * @return array Filtered and sorted list of files.
     *
     * @throws FinderException If an error occurs during the process.
     */
    public function find(array $criteria = [], ?string $path = null, array $sort = []): array
    {
        return $this->wrapInTry(
            fn() => $this->handle($criteria, $path, $sort),
            "Error in FileFinder find"
        );
    }

    /**
     * Searches files across multiple directories with optional criteria and sorting.
     *
     * @param array $criteria Search criteria recognized by Finder logic.
     * @param string|null $path Directory path to start search (default: root).
     * @param array $sort Sorting options recognized by Finder logic.
     * @return array Filtered and sorted list of files across multiple directories.
     *
     * @throws FinderException If an error occurs during the process.
     */
    public function search(array $criteria = [], ?string $path = null, array $sort = []): array
    {
        return $this->wrapInTry(
            fn() => $this->searchMultipleDirectories([$this->validatePath($path ?? $this->root)], $criteria, $sort),
            "Error during searchFiles"
        );
    }

    /**
     * Scans a directory and retrieves detailed file information.
     *
     * @param string|null $path Directory path to scan (default: root).
     * @return array List of files with detailed information.
     *
     * @throws FinderException If the scan operation fails.
     */
    public function scan(?string $path = null): array
    {
        return $this->wrapInTry(
            fn() => $this->filter(
                $this->map(
                    fn($item) => $this->getFileInfo($item, $path),
                    scandir($this->validatePath($path ?? $this->root)) ?: throw new FinderException("Failed to scan directory")
                )
            ),
            "Error during scandir"
        );
    }

    /**
     * Displays a tree structure of the specified directory.
     *
     * @param string|null $path Directory path to display as a tree (default: root).
     *
     * @throws FinderException If an error occurs during the operation.
     */
    public function showTree(?string $path = null): void
    {
        $this->wrapInTry(
            fn() => $this->displayDirectoryTree($this->validatePath($path ?? $this->root)),
            "Error displaying file tree"
        );
    }

    /**
     * Filters files up to a specified depth level.
     *
     * @param array $criteria Search criteria recognized by the Finder logic.
     * @param int $maxDepth Maximum depth for the filtering operation.
     * @param string|null $path Directory path to start filtering (default: root).
     * @return array Filtered list of files within the specified depth.
     *
     * @throws FinderException If an error occurs during filtering.
     */
    public function findByDepth(array $criteria = [], int $maxDepth = 0, ?string $path = null): array
    {
        return $this->wrapInTry(
            fn() => $this->filterWithDepthControl($criteria, $this->validatePath($path ?? $this->root), $maxDepth, []),
            "Error filtering files by depth"
        );
    }

    /**
     * Filters files from cache based on specified criteria.
     *
     * @param array $criteria Search criteria recognized by the Finder logic.
     * @param string|null $path Directory path to validate (default: root).
     * @return array Filtered list of files from cache.
     *
     * @throws FinderException If an error occurs during the process.
     */
    public function findByCache(array $criteria = [], ?string $path = null): array
    {
        return $this->wrapInTry(
            fn() => $this->filterWithCache($criteria, $this->validatePath($path ?? $this->root)),
            "Error during cacheFiles"
        );
    }

    /**
     * Filters files using a regex pattern.
     *
     * @param array $criteria Criteria including a `'pattern'` key for regex matching.
     * @param string|null $path Directory path to filter (default: root).
     * @return array Filtered list of files matching the regex.
     *
     * @throws FinderException If an error occurs during the process.
     */
    public function findByRegEx(array $criteria = [], ?string $path = null): array
    {
        return $this->wrapInTry(
            fn() => $this->filterWithRegex($criteria, $this->validatePath($path ?? $this->root)),
            "Error during regexFiles"
        );
    }

    /**
     * Retrieves detailed information about a file.
     *
     * @param string $item Name of the file.
     * @param string|null $path Directory path where the file resides.
     * @return array File details including name, path, size, permissions, and modification time.
     *
     * @throws FinderException If an error occurs during the retrieval process.
     */
    protected function getFileInfo(string $item, ?string $path): array
    {
        return $this->wrapInTry(function () use ($item, $path) {
            $fileInfo = $this->iteratorManager->FileInfo(
                $this->validatePath($path ?? $this->root) . DIRECTORY_SEPARATOR . $item
            );

            return [
                'name'         => $item,
                'realPath'     => $fileInfo->getRealPath(),
                'size'         => $fileInfo->getSize(),
                'permissions'  => $fileInfo->getPerms(),
                'lastModified' => date("F d Y H:i:s", $fileInfo->getMTime()),
            ];
        }, "Error retrieving file info");
    }
}
