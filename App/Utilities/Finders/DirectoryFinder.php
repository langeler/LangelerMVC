<?php

namespace App\Utilities\Finders;

use App\Abstracts\Data\Finder;
use App\Contracts\Data\FinderInterface;
use App\Utilities\Traits\Criteria\DirectoryCriteriaTrait;
use App\Utilities\Traits\Sort\DirectorySortTrait;

/**
 * Class DirectoryFinder
 *
 * Handles searching, filtering, and processing of directories.
 */
class DirectoryFinder extends Finder implements FinderInterface
{
    use DirectoryCriteriaTrait, DirectorySortTrait;

    /**
     * Finds directories based on given criteria and sorting options.
     *
     * @param array $criteria Search criteria.
     * @param string|null $path Starting path (default: root).
     * @param array $sort Sorting options.
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
     * @param array $criteria Search criteria.
     * @param string|null $path Starting path (default: root).
     * @param array $sort Sorting options.
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
     * @param array $criteria Search criteria.
     * @param string|null $path Starting directory path (default: root).
     * @param int $maxDepth Maximum depth for filtering.
     * @param array $sort Sorting options.
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
     * Filters directories from cache based on criteria.
     *
     * @param array $criteria Search criteria.
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
     * @param array $criteria Criteria including regex patterns.
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
            $fileInfo = $this->iteratorManager->FileInfo($this->validatePath($path ?? $this->root) . DIRECTORY_SEPARATOR . $item);
            return [
                'name' => $item,
                'type' => $fileInfo->isDir() ? 'directory' : 'file',
                'realPath' => $fileInfo->getRealPath(),
                'size' => $fileInfo->getSize(),
                'permissions' => $fileInfo->getPerms(),
                'lastModified' => date("F d Y H:i:s", $fileInfo->getMTime()),
            ];
        }, "Error retrieving file info");
    }
}
