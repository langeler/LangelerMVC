<?php

declare(strict_types=1);

namespace App\Abstracts\Data;

use App\Exceptions\Data\FinderException;        // Exception for errors occurring during finder operations.

use App\Utilities\Managers\IteratorManager;     // Manages and facilitates operations involving iterators.
use SplFileInfo;

use App\Utilities\Traits\{
    ApplicationPathTrait,
    ArrayTrait,              // Provides utility methods for array operations and transformations.
    ErrorTrait,              // Provides framework-aligned exception wrapping.
    LoopTrait,               // Adds support for iterating over data structures.
    ManipulationTrait,
    TypeCheckerTrait,
};
use App\Utilities\Traits\Patterns\PatternTrait;

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
    use ManipulationTrait;
    use LoopTrait;
    use PatternTrait;
    use TypeCheckerTrait;

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
     * @var array<string, int> Tracks iterator depth per discovered item.
     */
    protected array $itemDepths = [];

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
            'files' => ['composer.json'],
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
               $this->all($this->markers[$type], fn($element) => $callback($path . DIRECTORY_SEPARATOR . $element));
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
            function () use ($criteria, $path, $sort): array {
                $this->resetTraversalDepths();

                return !$this->isEmpty($sort)
                    ? $this->applySort($this->filterElements($criteria, $path ?? $this->root), $sort)
                    : $this->filterElements($criteria, $path ?? $this->root);
            },
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
        return $this->wrapFinder(
            fn() => $this->collectIteratorItems($this->fetchFiltrator($path, $criteria), $criteria),
            "Error during filtering"
        );
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
                            fn($fileInfo) => !$fileInfo instanceof SplFileInfo
                                ? false
                                : ($fileInfo->isDir() || $this->applyFilter($fileInfo, $criteria))
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
     * Apply a regex pattern to the current item's display name.
     *
     * `patternPath` remains the explicit path-based matcher; the generic `pattern`
     * criteria is intentionally name-based for a more predictable public API.
     *
     * @param mixed $fileInfo
     * @param string $pattern
     * @return bool
     */
    protected function filterByPattern(mixed $fileInfo, string $pattern): bool
    {
        $subject = $fileInfo instanceof SplFileInfo
            ? $fileInfo->getFilename()
            : (string) $fileInfo;

        return $this->match($pattern, $subject) === 1;
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

            $direction = $this->toLower((string) ($sortCriteria['direction'] ?? 'asc'));

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
     * Return the configured finder root.
     */
    public function getRoot(): string
    {
        return (string) $this->root;
    }

    /**
     * Enable or disable implicit caching for iterative finder operations.
     */
    public function useCache(bool $enabled = true): static
    {
        $this->cacheState = $enabled;

        return $this;
    }

    /**
     * Clear all cache data or the cache entry for a specific path.
     */
    public function clearCache(?string $path = null): static
    {
        if ($path === null) {
            $this->cache = [];
            $this->itemDepths = [];

            return $this;
        }

        $resolvedPath = $this->iteratorManager->FileInfo($path)->getRealPath() ?: $path;

        unset($this->cache[$resolvedPath], $this->cache[$path]);
        $this->itemDepths = [];

        return $this;
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
        return $this->wrapFinder(function () use ($path): string {
            $fileInfo = $this->iteratorManager->FileInfo($path);
            $realPath = $fileInfo->getRealPath();

            if (!$this->isString($realPath) || $realPath === '') {
                throw new FinderException("Path is invalid: $path");
            }

            if (!$fileInfo->isDir()) {
                throw new FinderException("Path is not a directory: $path");
            }

            return $realPath;
        }, "Error resolving path");
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
            $this->cache[$path] = $this->collectIteratorItems(
                $this->iteratorManager->RecursiveIteratorIterator(
                    $this->iteratorManager->RecursiveDirectoryIterator($path, [
                        'flag' => ['skipDots' => true],
                        'mode' => ['asFileInfo' => true]
                    ])
                )
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
    protected function filterWithRegex(array $criteria, string $path, array $sort = []): array
    {
        return $this->handle($criteria, $path, $sort);
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
    protected function displayDirectoryTree(string $path): string
    {
        return $this->wrapFinder(function () use ($path): string {
            $iterator = $this->iteratorManager->RecursiveTreeIterator(
                $this->iteratorManager->RecursiveCachingIterator(
                    $this->iteratorManager->RecursiveDirectoryIterator($path, ['flag' => ['skipDots' => true]])
                )
            );

            $lines = [];

            foreach ($iterator as $item) {
                $lines[] = (string) $item;
            }

            return $this->joinStrings(PHP_EOL, $lines)
                . ($this->isEmpty($lines) ? '' : PHP_EOL);
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
                $this->resetTraversalDepths();
                $results = [];

                foreach ($paths as $path) {
                    $results = $this->mergeSearchResults(
                        $results,
                        $this->filterElements($criteria, $path)
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
                $this->resetTraversalDepths();
                $iterator = $this->iteratorManager->RecursiveIteratorIterator(
                    $this->iteratorManager->RecursiveDirectoryIterator($path, ['flag' => ['skipDots' => true]]),
                    ['mode' => ['selfFirst'], 'maxDepth' => $maxDepth]
                );

                $results = [];
                $this->iteratorManager->setIterator($iterator);
                $this->iteratorManager->rewind();

                while ($this->iteratorManager->valid()) {
                    $fileInfo = $this->iteratorManager->current();
                    $this->rememberItemDepth($fileInfo, $this->iteratorManager->getDepth());

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
            function () use ($type, $settings, $criteria, $sort): array {
                $this->resetTraversalDepths();

                return $this->applySort(
                    $this->collectIteratorItems(
                        $this->iteratorManager->CallbackFilterIterator(
                            $this->iteratorManager->createIterator($type, $settings),
                            fn($fileInfo) => $this->applyFilter($fileInfo, $criteria)
                        ),
                        $criteria
                    ),
                    $sort
                );
            },
            "Error during custom iterator resolution"
        );
    }

    /**
     * Collects items from an iterator while preserving per-item depth metadata.
     *
     * @param \Iterator $iterator
     * @param array $criteria
     * @return array
     */
    protected function collectIteratorItems(\Iterator $iterator, array $criteria = []): array
    {
        $results = [];

        $this->iteratorManager->setIterator($iterator);
        $this->iteratorManager->rewind();

        while ($this->iteratorManager->valid()) {
            $item = $this->iteratorManager->current();
            $this->rememberItemDepth($item, $this->iteratorManager->getDepth());

            if ($criteria === [] || $this->applyFilter($item, $criteria)) {
                $results[$this->iteratorManager->key()] = $item;
            }

            $this->iteratorManager->next();
        }

        return $results;
    }

    /**
     * Records the resolved iterator depth for an item.
     *
     * @param mixed $item
     * @param int $depth
     * @return void
     */
    protected function rememberItemDepth(mixed $item, int $depth): void
    {
        $key = $this->itemDepthKey($item);

        if ($key !== null) {
            $this->itemDepths[$key] = $depth;
        }
    }

    /**
     * Resets per-traversal depth metadata so long-lived finder instances do not retain
     * stale iterator state across unrelated scans.
     *
     * Cached finder results repopulate their own depth metadata when the cache is built,
     * so clearing this transient state is safe at traversal boundaries.
     */
    protected function resetTraversalDepths(): void
    {
        $this->itemDepths = [];
    }

    /**
     * Returns the cached depth for an item.
     *
     * @param mixed $item
     * @return int
     */
    protected function getItemDepth(mixed $item): int
    {
        $key = $this->itemDepthKey($item);

        return $key !== null
            ? ($this->itemDepths[$key] ?? 0)
            : 0;
    }

    /**
     * Builds a stable depth-cache key for an iterator item.
     *
     * @param mixed $item
     * @return string|null
     */
    private function itemDepthKey(mixed $item): ?string
    {
        if ($item instanceof SplFileInfo) {
            return 'path:' . ($item->getRealPath() ?: $item->getPathname());
        }

        if ($this->isObject($item)) {
            return 'object:' . spl_object_id($item);
        }

        if ($this->isString($item)) {
            return 'string:' . $item;
        }

        return null;
    }

    /**
     * Resolve one or many search roots into validated absolute paths.
     *
     * @param string|array|null $paths
     * @return array<int, string>
     */
    protected function resolveSearchPaths(string|array|null $paths = null): array
    {
        $candidates = $paths === null
            ? [$this->root]
            : ($this->isArray($paths) ? $this->getValues($paths) : [$paths]);

        return $this->map(
            fn(string $candidate): string => $this->validatePath($candidate),
            $candidates
        );
    }

    /**
     * Merge search results by stable item identity to avoid duplicates across roots.
     *
     * @param array $results
     * @param array $items
     * @return array
     */
    protected function mergeSearchResults(array $results, array $items): array
    {
        foreach ($items as $key => $item) {
            $identity = $this->itemDepthKey($item) ?? (string) $key;
            $results[$identity] = $item;
        }

        return $results;
    }

    /**
     * Return a tree representation of the specified path.
     */
    public function tree(?string $path = null): string
    {
        return $this->displayDirectoryTree($this->validatePath($path ?? $this->root));
    }

    /**
     * Output a tree representation of the specified path.
     */
    public function showTree(?string $path = null): void
    {
        print($this->tree($path));
    }

    /**
     * Build a consistent metadata description for a filesystem item.
     *
     * @param SplFileInfo $fileInfo
     * @return array<string, mixed>
     */
    protected function describeItem(SplFileInfo $fileInfo): array
    {
        $realPath = $fileInfo->getRealPath() ?: $fileInfo->getPathname();

        return [
            'name' => $fileInfo->getFilename(),
            'type' => $fileInfo->isDir() ? 'directory' : 'file',
            'path' => $fileInfo->getPathname(),
            'realPath' => $realPath,
            'extension' => $fileInfo->isFile() ? $fileInfo->getExtension() : null,
            'size' => $fileInfo->getSize(),
            'permissions' => $fileInfo->getPerms(),
            'owner' => $fileInfo->getOwner(),
            'group' => $fileInfo->getGroup(),
            'modifiedAt' => $fileInfo->getMTime(),
            'accessedAt' => $fileInfo->getATime(),
            'createdAt' => $fileInfo->getCTime(),
            'readable' => $fileInfo->isReadable(),
            'writable' => $fileInfo->isWritable(),
            'executable' => $fileInfo->isExecutable(),
            'symlink' => $fileInfo->isLink(),
        ];
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
