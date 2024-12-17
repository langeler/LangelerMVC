<?php

namespace App\Utilities\Finders;

use App\Abstracts\Data\Finder;
use App\Contracts\Data\FinderInterface;
use App\Utilities\Traits\Criteria\FileCriteriaTrait;
use App\Utilities\Traits\Sort\FileSortTrait;

/**
 * Class FileFinder
 *
 * Handles searching, filtering, and processing of files.
 * Provides methods for scanning, filtering, and sorting files in directories.
 */
class FileFinder extends Finder implements FinderInterface
{
    use FileCriteriaTrait, FileSortTrait;

    /**
     * Finds files based on given criteria and sorting options.
     *
     * @param array $criteria Search criteria to filter files.
     * @param string|null $path Directory path to search (default: root).
     * @param array $sort Sorting options.
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
     * @param array $criteria Search criteria to filter files.
     * @param string|null $path Directory path to start search (default: root).
     * @param array $sort Sorting options.
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
     * @param array $criteria Search criteria to filter files.
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
     * @param array $criteria Search criteria to filter files.
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
     * @param array $criteria Criteria including regex patterns for filtering files.
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
            $fileInfo = $this->iteratorManager->FileInfo($this->validatePath($path ?? $this->root) . DIRECTORY_SEPARATOR . $item);
            return [
                'name' => $item,
                'realPath' => $fileInfo->getRealPath(),
                'size' => $fileInfo->getSize(),
                'permissions' => $fileInfo->getPerms(),
                'lastModified' => date("F d Y H:i:s", $fileInfo->getMTime()),
            ];
        }, "Error retrieving file info");
    }
}
