<?php

declare(strict_types=1);

namespace App\Abstracts\Data;

use App\Exceptions\Data\FinderException;        // Exception for errors occurring during finder operations.

use App\Utilities\Managers\IteratorManager;     // Manages and facilitates operations involving iterators.

use App\Utilities\Traits\{
    ApplicationPathTrait,
    ArrayTrait,              // Provides utility methods for array operations and transformations.
    ErrorTrait,              // Provides framework-aligned exception wrapping.
    LoopTrait,               // Adds support for iterating over data structures.
};

/**
 * Abstract class Finder
 *
 * Provides a base class for locating, filtering, and validating files and directories.
 * It utilizes advanced iterators, caching mechanisms, and reusable traits for streamlined functionality.
 */
abstract class Finder
{
    use ApplicationPathTrait;
    use ErrorTrait;
    use ArrayTrait {
        search as private;
        search as protected arraySearch;
    }
    use LoopTrait;

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
     * @var array<string, array<int, string>>
     */
    protected array $foundMarkers = [
        'files' => [],
        'directories' => [],
    ];

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
    protected function wrapFinder(callable $callback, string $message): mixed
    {
        return $this->wrapInTry(
            $callback,
            fn(\Throwable $caught) => new FinderException("$message: " . $caught->getMessage(), 0, $caught)
        );
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
        $this->wrapFinder(function () {
            $fallbackRoot = $this->frameworkBasePath();

            if ($this->isDirectory($fallbackRoot) && $this->isValidRootDirectory($fallbackRoot)) {
                $this->root = $fallbackRoot;

                return;
            }

            $path = $this->isEmpty(getcwd()) ? $fallbackRoot : getcwd();

            while (true) {
                if ($this->isDirectory($path) && $this->isValidRootDirectory($path)) {
                    $this->root = $path;

                    return;
                }

                $parent = dirname($path);

                if ($parent === $path) {
                    break;
                }

                $path = $parent;
            }

            throw new FinderException("Unable to determine the project root directory.");
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
                $this->arraySearch($this->markers['files'], $fileInfo->getFilename()) !== false,
            $this->iteratorManager->isDir() =>
                $this->arraySearch($this->markers['directories'], $fileInfo->getFilename()) !== false,
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
        $this->arraySearch($this->foundMarkers[$type], $marker) === false
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
        return $this->wrapFinder(
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
        return $this->wrapFinder(function () use ($criteria, $path) {
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
        return $this->wrapFinder(
            fn() => match (true) {
                // Use RecursiveRegexIterator when a pattern is provided
                !$this->isEmpty($criteria['pattern'] ?? []) =>
                    $this->iteratorManager->RecursiveIteratorIterator(
                        $this->iteratorManager->RecursiveRegexIterator(
                            $this->iteratorManager->RecursiveDirectoryIterator($path, ['flag' => ['skipDots' => true]]),
                            is_array($criteria['pattern'])
                                ? (string) $this->current($criteria['pattern'])
                                : (string) $criteria['pattern']
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
     * Applies finder criteria by dispatching to trait-provided `filterBy...` methods.
     */
    protected function applyFilter(mixed $fileInfo, array $criteria): bool
    {
        return $this->wrapFinder(function () use ($fileInfo, $criteria) {
            foreach ($criteria as $key => $value) {
                if ($key === 'pattern') {
                    continue;
                }

                $method = 'filterBy' . ucfirst((string) $key);

                if (!$this->methodExists($this, $method)) {
                    continue;
                }

                $reflectionMethod = new \ReflectionMethod($this, $method);
                $arguments = $reflectionMethod->getNumberOfParameters() <= 1 || $value === true
                    ? []
                    : ($this->isArray($value) ? $this->getValues($value) : [$value]);

                if (!$this->$method($fileInfo, ...$arguments)) {
                    return false;
                }
            }

            return true;
        }, "Error applying filter");
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
        return $this->wrapFinder(function () use ($items, $sortCriteria) {
            if ($this->isEmpty($sortCriteria)) {
                return $items;
            }

            $sortBy = (string) ($sortCriteria['callback'] ?? $sortCriteria['by'] ?? '');

            if ($sortBy === '') {
                return $items;
            }

            $method = 'sortBy' . ucfirst($sortBy);

            if (!$this->methodExists($this, $method)) {
                throw new FinderException("Unsupported sort criteria '{$sortBy}'.");
            }

            $direction = strtolower((string) ($sortCriteria['direction'] ?? 'asc'));

            uasort($items, function ($left, $right) use ($method, $direction): int {
                $result = $this->{$method}($left, $right);
                return $direction === 'desc' ? $result * -1 : $result;
            });

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
    protected function validatePath(string $path): string
    {
        return $this->wrapFinder(
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
        return $this->wrapFinder(
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
    private function populateCache(string $path): void
    {
        $this->wrapFinder(function () use ($path) {
            $this->iteratorManager->setIterator(
                $this->iteratorManager->RecursiveIteratorIterator(
                    $this->iteratorManager->RecursiveDirectoryIterator($path, [
                        'flag' => ['skipDots' => true],
                        'mode' => ['asFileInfo' => true]
                    ])
                )
            );

            $this->cache[$path] = $this->iteratorManager->toArray($this->iteratorManager->getIterator());
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
        return $this->wrapFinder(
            function () use ($criteria, $path): array {
                $pattern = $this->isEmpty($criteria['pattern'] ?? [])
                    ? '/.*/'
                    : (is_array($criteria['pattern'])
                        ? (string) $this->current($criteria['pattern'])
                        : (string) $criteria['pattern']);

                $results = $this->iteratorManager->toArray(
                    $this->iteratorManager->RecursiveIteratorIterator(
                        $this->iteratorManager->RecursiveRegexIterator(
                            $this->iteratorManager->RecursiveDirectoryIterator($path, ['flag' => ['skipDots' => true]]),
                            $pattern
                        )
                    )
                );

                return $this->filter(
                    $results,
                    fn($fileInfo): bool => $this->applyFilter($fileInfo, $criteria)
                );
            },
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
    protected function filterWithCache(array $criteria = [], ?string $path = null, array $sort = []): array
    {
        return $this->wrapFinder(function () use ($criteria, $path, $sort) {
            $validatedPath = $this->validatePath($path ?? $this->root);

            if ($this->isEmpty($this->cache[$validatedPath] ?? [])) {
                $this->populateCache($validatedPath);
            }

            $filtered = $this->filter(
                $this->cache[$validatedPath],
                fn($item): bool => $this->applyFilter($item, $criteria)
            );

            return $this->isEmpty($sort)
                ? $filtered
                : $this->applySort($filtered, $sort);
        }, "Error filtering cache");
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
        $this->wrapFinder(function () use ($path) {
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
        return $this->wrapFinder(
            function () use ($paths, $criteria, $sort): array {
                $results = [];

                foreach ($paths as $path) {
                    $results = $this->merge(
                        $results,
                        $this->filterElements($criteria, $this->validatePath($path))
                    );
                }

                return $this->applySort($results, $sort);
            },
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
        return $this->wrapFinder(
            function () use ($criteria, $path, $maxDepth, $sort): array {
                $iterator = $this->iteratorManager->RecursiveIteratorIterator(
                    $this->iteratorManager->RecursiveDirectoryIterator($path, ['flag' => ['skipDots' => true]]),
                    ['mode' => ['selfFirst'], 'maxDepth' => $maxDepth]
                );

                $results = [];
                $this->iteratorManager->setIterator($iterator);
                $this->iteratorManager->rewind();

                while ($this->iteratorManager->valid()) {
                    $fileInfo = $this->iteratorManager->current();

                    if ($this->applyFilter($fileInfo, $criteria)) {
                        $results[$this->iteratorManager->key()] = $fileInfo;
                    }

                    $this->iteratorManager->next();
                }

                return $this->applySort($results, $sort);
            },
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
        return $this->wrapFinder(
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
