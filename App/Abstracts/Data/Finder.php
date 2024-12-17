<?php

namespace App\Abstracts\Data;

use App\Exceptions\Data\FinderException;
use App\Utilities\Managers\IteratorManager;
use Throwable;

use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\ExistenceCheckerTrait;
use App\Utilities\Traits\LoopTrait;
use App\Utilities\Traits\TypeCheckerTrait;

/**
 * Abstract class Finder
 *
 * Provides a base class for locating, filtering, and validating files and directories.
 * It utilizes advanced iterators, caching mechanisms, and reusable traits for streamlined functionality.
 */
abstract class Finder
{
    use ArrayTrait, ExistenceCheckerTrait, LoopTrait, TypeCheckerTrait;

    /**
     * @var string|null $root The root directory path where searches will start.
     */
    protected ?string $root = '/';

    /**
     * @var array $data Holds filtered or processed directory and file data.
     */
    protected array $data = [];

    /**
     * @var array $cache Stores cached directory and file information for optimized lookups.
     */
    protected array $cache = [];

    /**
     * @var bool $cacheState Determines whether caching is enabled or disabled.
     */
    protected bool $cacheState = true;

    /**
     * @var array $markers Defines required markers (files and directories) to identify the root directory.
     */
    protected readonly array $markers;

    /**
     * Constructor method for Finder
     *
     * Initializes the class properties and markers, and determines the root directory
     * based on the presence of specific project markers (e.g., `composer.json`).
     *
     * @param IteratorManager $iteratorManager An instance of IteratorManager to handle directory iterations.
     *
     * @throws FinderException If the root directory cannot be determined.
     */
    public function __construct(protected IteratorManager $iteratorManager)
    {
        // Define required files and directories as markers for identifying the project root
        $this->markers = [
            'files' => ['composer.json', '.env', 'composer.lock'],
            'directories' => ['App', 'Config', 'Public'],
        ];

        // Initialize and determine the root directory
        $this->setRoot();
    }

    /**
     * Wraps the given callback in a try/catch for consistent error handling.
     *
     * @param callable $callback The callback to execute.
     * @param string $message Custom error message to display on failure.
     *
     * @return mixed The result of the callback function.
     * @throws FinderException If the callback throws an exception.
     */
    private function wrapInTry(callable $callback, string $message): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            throw new FinderException("$message: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Sets the root directory by locating project root markers.
     *
     * This method iterates upward through directories until the required project markers
     * (files and directories) are found. Once validated, the root path is set.
     *
     * @throws FinderException If an error occurs while setting the root directory.
     */
    protected function setRoot(): void
    {
        $this->wrapInTry(function () {
            $path = $this->isEmpty(getcwd()) ? '/' : getcwd();

            $this->until($path, '/', function () use (&$path) {
                $iterator = $this->iteratorManager->RecursiveCallbackFilterIterator(
                    $this->iteratorManager->ParentIterator(
                        $this->iteratorManager->RecursiveDirectoryIterator($path, [
                            'flag' => ['skipDots' => true],
                            'mode' => ['asFileInfo' => true]
                        ])
                    ),
                    fn($fileInfo) => $this->isMarkerMatch($fileInfo)
                );

                $this->iteratorManager->setIterator($iterator);
                $this->iteratorManager->applyCallback($iterator, fn($fileInfo) => $this->collectMarker($fileInfo));

                $this->allMarkersFound() && $this->isValidRootDirectory($path) && $this->isDirectory($path)
                    ? ($this->root = $path) && false
                    : $path = $this->prev($path);
            });
        }, "Error in setRoot");
    }

    /**
     * Validates if the identified directory has the necessary project markers.
     *
     * @param string $path The directory path to validate.
     *
     * @return bool True if all required files and directories exist, otherwise false.
     */
    protected function isValidRootDirectory(string $path): bool
    {
        return $this->hasRequiredElements($path, 'directories', 'is_dir')
            && $this->hasRequiredElements($path, 'files', 'is_file');
    }

    /**
     * Determines if the fileInfo matches any of the markers.
     *
     * @param mixed $fileInfo The file or directory info object to evaluate.
     *
     * @return bool True if the file or directory matches the specified markers, otherwise false.
     */
    protected function isMarkerMatch($fileInfo): bool
    {
        return match (true) {
            $this->iteratorManager->isFile() =>
                $this->search($this->markers['files'], $fileInfo->getFilename()) !== false,
            $this->iteratorManager->isDir() =>
                $this->search($this->markers['directories'], $fileInfo->getFilename()) !== false,
            default => false,
        };
    }

    /**
     * Collects a marker if it matches either file or directory markers.
     *
     * @param mixed $fileInfo The file or directory info object being evaluated.
     *
     * @return bool True if all markers have been found, otherwise false.
     */
    protected function collectMarker($fileInfo): bool
    {
        return !$this->isMarkerMatch($fileInfo)
            ? $this->allMarkersFound()
            : (match (true) {
                $fileInfo->isFile() && $this->isFile($fileInfo?->getRealPath()) =>
                    $this->addToMarkers('files', $fileInfo->getFilename()),
                $fileInfo->isDir() && $this->isDirectory($fileInfo?->getRealPath()) =>
                    $this->addToMarkers('directories', $fileInfo->getFilename()),
                default => null
            }) ?? $this->allMarkersFound();
    }

    /**
     * Adds a marker to the appropriate list if not already present.
     *
     * @param string $type The type of marker ('files' or 'directories').
     * @param string $marker The filename or directory name of the marker.
     */
    private function addToMarkers(string $type, string $marker): void
    {
        $this->foundMarkers[$type] ??= [];
        !$this->keyExists($this->foundMarkers[$type], $marker)
            ? $this->foundMarkers[$type][] = $marker
            : null;
    }

    /**
     * Check if all required markers are found within the current directory.
     *
     * @return bool True if all required markers are found, otherwise false.
     */
    protected function allMarkersFound(): bool
    {
        return $this->hasAllMarkers('files') && $this->hasAllMarkers('directories');
    }

    /**
     * Checks if all markers of a specific type are found.
     *
     * @param string $type The type of markers to check ('files' or 'directories').
     *
     * @return bool True if all markers of the specified type are found, otherwise false.
     */
    private function hasAllMarkers(string $type): bool
    {
        return $this->isEmpty($this->diff($this->markers[$type], $this->foundMarkers[$type] ?? []));
    }

    /**
     * Checks for required elements (files or directories) in the specified path.
     *
     * @param string $path The directory path to validate.
     * @param string $type The type of elements to check ('files' or 'directories').
     * @param callable $callback A callback function to verify the existence of the element.
     *
     * @return bool True if all required elements exist in the specified path, otherwise false.
     */
    private function hasRequiredElements(string $path, string $type, callable $callback): bool
    {
        return !$this->isEmpty($this->markers[$type]) &&
               $this->all($this->markers[$type], fn($element) => $callback("$path/$element"));
    }

    /**
     * Handles the filtering and sorting of items.
     *
     * This method filters files and directories based on the provided criteria
     * and optionally applies sorting to the filtered results.
     *
     * @param array $criteria The filtering criteria (e.g., patterns, conditions).
     * @param string|null $path The root path for filtering; defaults to the current root.
     * @param array $sort Sorting rules to apply to the filtered results.
     *
     * @return array The filtered and optionally sorted results.
     * @throws FinderException If an error occurs during filtering or sorting.
     */
    protected function handle(array $criteria = [], ?string $path = null, array $sort = []): array
    {
        return $this->wrapInTry(
            fn() => !$this->isEmpty($sort)
                ? $this->applySort($this->filterElements($criteria, $path ?? $this->root), $sort)
                : $this->filterElements($criteria, $path ?? $this->root),
            "Error in handle"
        );
    }

    /**
     * Filters files and directories based on criteria.
     *
     * This method retrieves the appropriate iterator for a given path and criteria,
     * filters the items, and returns the filtered results.
     *
     * @param array $criteria The filtering criteria (e.g., patterns, conditions).
     * @param string $path The directory path to filter items from.
     *
     * @return array The filtered files and directories.
     * @throws FinderException If an error occurs during filtering.
     */
    private function filterElements(array $criteria, string $path): array
    {
        return $this->wrapInTry(function () use ($criteria, $path) {
            $this->iteratorManager->setIterator($this->fetchFiltrator($path, $criteria));
            $this->iteratorManager->rewind();

            return $this->filter(
                $this->iteratorManager->toArray($this->iteratorManager->getIterator()),
                fn($fileInfo) => $this->applyFilter($fileInfo, $criteria)
            );
        }, "Error during filtering");
    }

    /**
     * Fetches the appropriate iterator based on criteria.
     *
     * Determines the best iterator type to use depending on the presence of filtering patterns
     * or cache state and returns the iterator for use in filtering.
     *
     * @param string $path The directory path to iterate over.
     * @param array $criteria The filtering criteria, including optional regex patterns.
     *
     * @return \Iterator The appropriate iterator for filtering.
     * @throws FinderException If an error occurs during iterator configuration.
     */
    private function fetchFiltrator(string $path, array $criteria): \Iterator
    {
        return $this->wrapInTry(
            fn() => match (true) {
                // Use RecursiveRegexIterator when a pattern is provided
                !$this->isEmpty($criteria['pattern']) =>
                    $this->iteratorManager->RecursiveIteratorIterator(
                        $this->iteratorManager->RecursiveRegexIterator(
                            $this->iteratorManager->RecursiveDirectoryIterator($path, ['flag' => ['skipDots' => true]]),
                            $this->current($criteria['pattern'])
                        )
                    ),
                // Use caching iterator if cacheState is enabled
                $this->cacheState =>
                    $this->iteratorManager->RecursiveIteratorIterator(
                        $this->iteratorManager->RecursiveCachingIterator(
                            $this->iteratorManager->RecursiveDirectoryIterator($path, ['flag' => ['skipDots' => true]])
                        )
                    ),
                // Default to CallbackFilterIterator when no pattern or cache is used
                default =>
                    $this->iteratorManager->RecursiveIteratorIterator(
                        $this->iteratorManager->RecursiveCallbackFilterIterator(
                            $this->iteratorManager->RecursiveDirectoryIterator($path, ['flag' => ['skipDots' => true]]),
                            fn($fileInfo) => $this->applyFilter($fileInfo, $criteria)
                        )
                    )
            },
            "Error configuring iterator"
        );
    }

    /**
     * Applies sorting to the filtered items.
     *
     * This method sorts the provided array of items recursively based on the provided criteria.
     *
     * @param array $items The array of filtered items to be sorted.
     * @param array $sortCriteria Sorting rules to apply (e.g., ascending or descending order).
     *
     * @return array The sorted array of items.
     * @throws FinderException If an error occurs during sorting.
     */
    private function applySort(array $items, array $sortCriteria = []): array
    {
        return $this->wrapInTry(function () use ($items) {
            $this->sortRecursive($items);
            return $items;
        }, "Error during sorting");
    }

    /**
     * Validates the given path.
     *
     * This method ensures that the provided path is valid by resolving it using `resolvePath`.
     *
     * @param string $path The path to validate.
     * @return string The validated path.
     * @throws FinderException If the path cannot be resolved.
     */
    private function validatePath(string $path): string
    {
        return $this->wrapInTry(
            fn() => $this->resolvePath($path),
            "Error validating path"
        );
    }

    /**
     * Resolves the real path and ensures it is a valid directory.
     *
     * This method checks the existence, validity, and type of the provided path.
     * It ensures the path exists and points to a directory.
     *
     * @param string $path The path to resolve.
     * @return string The resolved real path.
     * @throws FinderException If the path does not exist, is invalid, or is not a directory.
     */
    protected function resolvePath(string $path): string
    {
        return $this->wrapInTry(
            fn() => match (true) {
                // Path does not exist
                !$this->iteratorManager->FileInfo($path) =>
                    throw new FinderException("Path does not exist: $path"),
                // Path cannot be resolved
                !$this->iteratorManager->FileInfo($path)->getRealPath() =>
                    throw new FinderException("Path is invalid: $path"),
                // Path is not a directory
                !$this->iteratorManager->FileInfo($path)->isDir() =>
                    throw new FinderException("Path is not a directory: $path"),
                // Path is valid
                default => $this->iteratorManager->FileInfo($path)->getRealPath(),
            },
            "Error resolving path"
        );
    }

    /**
     * Populates the cache with the entire directory and file structure under the root directory.
     *
     * This method uses a `RecursiveCachingIterator` to iterate over the root directory and
     * store relevant file and directory information into the cache.
     *
     * @throws FinderException If an error occurs while populating the cache.
     */
    private function populateCache(): void
    {
        $this->wrapInTry(function () {
            // Set up the caching iterator for the root directory
            $this->iteratorManager->setIterator(
                $this->iteratorManager->RecursiveCachingIterator(
                    $this->iteratorManager->RecursiveDirectoryIterator($this->root, [
                        'flag' => ['skipDots' => true],
                        'mode' => ['asFileInfo' => true]
                    ])
                )
            );

            // Populate the cache with mapped file and directory information
            $this->cache = $this->map(
                fn($item) => [
                    'path' => $item?->getRealPath(),
                    'name' => $item->getFilename(),
                    'type' => $item->isDirectory($item?->getRealPath())
                        ? 'directory'
                        : ($item->isFile($item?->getRealPath()) ? 'file' : 'unknown'),
                    'size' => $item->getSize(),
                    'permissions' => $item->getPerms(),
                ],
                $this->iteratorManager->toArray($this->iteratorManager->getIterator())
            );
        }, "Error populating cache");
    }

    /**
     * Filters directories and files using regex.
     *
     * This method applies a `RecursiveRegexIterator` to filter directories and files
     * based on a specified regular expression pattern.
     *
     * @param array $criteria The filtering criteria, including an optional 'pattern' key for regex.
     * @param string $path The directory path to filter files and directories from.
     *
     * @return array The filtered items matching the regex pattern.
     * @throws FinderException If an error occurs during regex filtering.
     */
    protected function filterWithRegex(array $criteria, string $path): array
    {
        return $this->wrapInTry(
            fn() => $this->iteratorManager->toArray(
                $this->iteratorManager->RecursiveIteratorIterator(
                    $this->iteratorManager->RecursiveRegexIterator(
                        $this->iteratorManager->RecursiveDirectoryIterator($path, ['flag' => ['skipDots' => true]]),
                        $this->isEmpty($criteria['pattern']) ? '/.*/' : $criteria['pattern']
                    )
                )
            ),
            "Error during regex filtering"
        );
    }

    /**
     * Filters the cached data based on the given criteria.
     *
     * Ensures the cache is populated if it is empty or unset. The method applies filtering
     * using the provided criteria and optionally applies sorting to the filtered results.
     *
     * @param array $criteria Filtering conditions to apply.
     * @param array $sort Optional sorting rules to apply to the filtered results.
     *
     * @return array The filtered (and optionally sorted) cached data.
     * @throws FinderException If an error occurs during filtering or sorting.
     */
    protected function filterWithCache(array $criteria = [], array $sort = []): array
    {
        return $this->wrapInTry(
            fn() => $this->isEmpty($this->cache) && $this->populateCache() ?: $this->isEmpty($sort)
                ? $this->filter($this->cache, fn($item) => $this->applyFilter($item, $criteria))
                : $this->applySort(
                    $this->filter($this->cache, fn($item) => $this->applyFilter($item, $criteria)),
                    $sort
                ),
            "Error filtering cache"
        );
    }

    /**
     * Displays a directory tree structure.
     *
     * This method uses a `RecursiveTreeIterator` to iterate through the specified directory
     * and outputs its hierarchical structure.
     *
     * @param string $path The root directory path to display as a tree structure.
     *
     * @throws FinderException If an error occurs while displaying the directory tree.
     */
    protected function displayDirectoryTree(string $path): void
    {
        $this->wrapInTry(function () use ($path) {
            $this->iteratorManager->setIterator(
                $this->iteratorManager->RecursiveTreeIterator(
                    $this->iteratorManager->RecursiveIteratorIterator(
                        $this->iteratorManager->RecursiveDirectoryIterator($path, ['flag' => ['skipDots' => true]])
                    )
                )
            );

            $this->each($this->iteratorManager->getIterator(), fn($key, $item) => print($item . PHP_EOL));
        }, "Error displaying directory tree");
    }

    /**
     * Searches across multiple directories with criteria and optional sorting.
     *
     * This method allows searching for files and directories across multiple paths,
     * applying filtering based on criteria, and optionally sorting the results.
     *
     * @param array $paths The list of directory paths to search in.
     * @param array $criteria The filtering conditions to apply.
     * @param array $sort Optional sorting rules to apply to the filtered results.
     *
     * @return array The filtered and sorted search results.
     * @throws FinderException If an error occurs during the search.
     */
    protected function searchMultipleDirectories(array $paths, array $criteria = [], array $sort = []): array
    {
        return $this->wrapInTry(
            fn() => $this->applySort(
                $this->iteratorManager->toArray(
                    $this->iteratorManager->RecursiveIteratorIterator(
                        $this->iteratorManager->RecursiveCallbackFilterIterator(
                            $this->iteratorManager->AppendIterator(
                                ...$this->map(
                                    fn($path) => $this->iteratorManager->RecursiveDirectoryIterator(
                                        $this->validatePath($path),
                                        ['flag' => ['skipDots' => true]]
                                    ),
                                    $paths
                                )
                            ),
                            fn($fileInfo) => $this->applyFilter($fileInfo, $criteria)
                        )
                    )
                ),
                $sort
            ),
            "Error during multi-directory search"
        );
    }

    /**
     * Filters directories and files with depth control.
     *
     * Allows filtering of files and directories up to a specific depth level.
     * Optional sorting can be applied to the filtered results.
     *
     * @param array $criteria The filtering conditions to apply.
     * @param string $path The root directory path to filter files and directories from.
     * @param int $maxDepth The maximum depth level to traverse.
     * @param array $sort Optional sorting rules to apply to the filtered results.
     *
     * @return array The filtered and optionally sorted results.
     * @throws FinderException If an error occurs during depth-controlled filtering.
     */
    protected function filterWithDepthControl(array $criteria, string $path, int $maxDepth, array $sort = []): array
    {
        return $this->wrapInTry(
            fn() => $this->applySort(
                $this->iteratorManager->toArray(
                    $this->iteratorManager->RecursiveIteratorIterator(
                        $this->iteratorManager->RecursiveCallbackFilterIterator(
                            $this->iteratorManager->RecursiveDirectoryIterator($path, ['flag' => ['skipDots' => true]]),
                            fn($fileInfo) => $this->applyFilter($fileInfo, $criteria)
                        ),
                        ['mode' => ['selfFirst'], 'maxDepth' => $maxDepth]
                    )
                ),
                $this->isEmpty($sort) ? [] : $sort
            ),
            "Error during depth-controlled filtering"
        );
    }

    /**
     * Configures a custom iterator for specific types and criteria.
     *
     * This method allows the creation of custom iterators, applying filters and optional
     * sorting rules to process specific data types.
     *
     * @param string $type The type of iterator to create (e.g., files, directories).
     * @param array $settings Configuration options for the iterator.
     * @param array $criteria The filtering conditions to apply.
     * @param array $sort Optional sorting rules to apply to the filtered results.
     *
     * @return array The filtered and optionally sorted results.
     * @throws FinderException If an error occurs while configuring the iterator.
     */
    protected function customIterator(string $type, array $settings = [], array $criteria = [], array $sort = []): array
    {
        return $this->wrapInTry(
            fn() => $this->applySort(
                $this->iteratorManager->toArray(
                    $this->iteratorManager->CallbackFilterIterator(
                        $this->iteratorManager->createIterator($type, $settings),
                        fn($fileInfo) => $this->applyFilter($fileInfo, $criteria)
                    )
                ),
                $sort
            ),
            "Error during custom iterator resolution"
        );
    }

    /**
     * Abstract method to find files and directories.
     *
     * This method must be implemented in derived classes to allow flexible
     * searching based on provided criteria, paths, and optional sorting rules.
     *
     * @param array $criteria The filtering conditions to apply.
     * @param string|null $path The root path for searching; defaults to null.
     * @param array $sort Optional sorting rules to apply.
     *
     * @return array The search results matching the specified criteria.
     */
    abstract public function find(array $criteria = [], ?string $path = null, array $sort = []): array;
}
