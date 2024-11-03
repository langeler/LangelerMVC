<?php

namespace App\Abstracts\Data;

use App\Exceptions\Data\FinderException;
use App\Utilities\Managers\IteratorManager;
use Throwable;

abstract class Finder
{
    protected ?string $root = '/';
    protected array $data = [];  // Replace temporary result array with class property
    protected array $cache = [];
    protected bool $cacheState = true;
    protected readonly array $markers;

    public function __construct(protected IteratorManager $iteratorManager)
    {
        $this->markers = [
            'files' => ['composer.json', '.env', 'composer.lock'],
            'directories' => ['App', 'Config', 'Public'],
        ];

        $this->setRoot();
    }

    /**
     * Sets the root directory by locating project root markers.
     */
    protected function setRoot(): void
    {
        $currentPath = getcwd() ?: '/';

        try {
            while ($currentPath !== '/') {
                $filteredIterator = $this->iteratorManager->RecursiveCallbackFilterIterator(
                    $this->iteratorManager->ParentIterator(
                        $this->iteratorManager->RecursiveDirectoryIterator($currentPath, [
                            'flag' => ['skipDots' => true],
                            'mode' => ['asFileInfo' => true]
                        ])
                    ),
                    fn($fileInfo) => $this->isMarkerMatch($fileInfo)
                );

                $this->iteratorManager->setIterator($filteredIterator);
                $this->iteratorManager->rewind();

                $this->iteratorManager->applyCallback($filteredIterator, fn($fileInfo) => $this->collectMarker($fileInfo));

                if ($this->allMarkersFound() && $this->isValidRootDirectory($currentPath)) {
                    $this->root = $currentPath;
                    break;
                }

                $currentPath = dirname($currentPath);
            }
        } catch (Throwable $e) {
            throw new FinderException("Error in setRoot: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Validates if the identified directory has the necessary project markers.
     */
    protected function isValidRootDirectory(string $path): bool
    {
        return $this->hasRequiredElements($path, 'directories', 'is_dir')
            && $this->hasRequiredElements($path, 'files', 'is_file');
    }

    /**
     * Determines if the fileInfo matches any of the markers.
     */
    protected function isMarkerMatch($fileInfo): bool
    {
        return match (true) {
            $this->iteratorManager->isFile() => in_array($fileInfo->getFilename(), $this->markers['files'], true),
            $this->iteratorManager->isDir() => in_array($fileInfo->getFilename(), $this->markers['directories'], true),
            default => false,
        };
    }

    /**
     * Collects a marker if it matches either file or directory markers.
     */
    protected function collectMarker($fileInfo): bool
    {
        if ($this->isMarkerMatch($fileInfo)) {
            match (true) {
                $this->iteratorManager->isFile() => $this->addToMarkers('files', $fileInfo->getFilename()),
                $this->iteratorManager->isDir() => $this->addToMarkers('directories', $fileInfo->getFilename()),
                default => null,
            };
        }

        return $this->allMarkersFound();
    }

    /**
     * Check if all required markers are found within the current directory.
     */
    protected function allMarkersFound(): bool
    {
        return $this->hasAllMarkers('files') && $this->hasAllMarkers('directories');
    }

    /**
     * Adds a marker to the appropriate list if not already present.
     */
    private function addToMarkers(string $type, string $marker): void
    {
        if (!in_array($marker, $this->markers[$type], true)) {
            $this->markers[$type][] = $marker;
        }
    }

    /**
     * Checks if all markers of a specific type are found.
     */
    private function hasAllMarkers(string $type): bool
    {
        $markerList = array_intersect($this->markers[$type], $this->markers[$type]);
        return count($markerList) === count($this->markers[$type]);
    }

    /**
     * Checks for required elements (files or directories) in the specified path.
     */
    private function hasRequiredElements(string $path, string $type, string $function): bool
    {
        foreach ($this->markers[$type] as $element) {
            if (!$function("$path/$element")) {
                return false;
            }
        }
        return true;
    }

    public function handle(array $criteria = [], ?string $path = null, array $sort = []): array
    {
        try {
            $items = $this->filter($criteria, $path ?? $this->root);

            return !empty($sort) ? $this->applySort($items, $sort) : $items;
        } catch (Throwable $e) {
            throw new FinderException("Error in handle: " . $e->getMessage(), 0, $e);
        }
    }

protected function filter(array $criteria, string $path): array
    {
        try {
            if (empty($path) || !is_dir($path)) {
                throw new FinderException("Invalid or empty path: {$path}");
            }

            // Set up iterator and begin recursive filtering
            $this->iteratorManager->setIterator($this->fetchFiltrator($path, $criteria));
            $this->iteratorManager->rewind();
            $this->data = [];

            while ($this->iteratorManager->valid()) {
                $current = $this->iteratorManager->current();

                // Skip if not within depth criteria
                if (isset($criteria['depth']) && !$this->filterByDepth($current, $criteria['depth'])) {
                    $this->iteratorManager->next();
                    continue;
                }

                // Filter and add matching directories/files to results
                if ($this->applyFilter($current, $criteria)) {
                    $this->data[] = $current->getPathname();
                }

                // Ensure subdirectory traversal if `hasChildren`
                if ($this->iteratorManager->isDir() && $this->iteratorManager->hasChildren()) {
                    $this->iteratorManager->getChildren();
                }

                $this->iteratorManager->next();
            }

            return $this->data;

        } catch (Throwable $e) {
            throw new FinderException("Error during filtering: " . $e->getMessage());
        }
    }

protected function fetchFiltrator(string $path, array $criteria): \Iterator
    {
        try {
            return match (true) {
                // Pattern criteria with RecursiveRegexIterator for regex filtering
                !empty($criteria['pattern']) =>
                    $this->iteratorManager->RecursiveIteratorIterator(
                        $this->iteratorManager->RecursiveRegexIterator(
                            $this->iteratorManager->RecursiveDirectoryIterator($path, [
                                'flag' => ['skipDots' => true],
                                'mode' => ['asFileInfo' => true]
                            ]),
                            current($criteria['pattern']),  // The actual regex pattern
                            ['mode' => ['match' => true]]
                        ),
                        ['mode' => ['selfFirst' => true]]
                    ),

                // Caching enabled, wrap CachingIterator with CallbackFilter and DirectoryIterator
                $this->cacheState =>
                    $this->iteratorManager->RecursiveIteratorIterator(
                        $this->iteratorManager->RecursiveCachingIterator(
                            $this->iteratorManager->RecursiveDirectoryIterator($path, [
                                'flag' => ['skipDots' => true],
                                'mode' => ['asFileInfo' => true]
                            ]),
                            ['flag' => ['fullCache' => true]]
                        ),
                        ['mode' => ['selfFirst' => true]]
                    ),

                // Default: Apply CallbackFilter within RecursiveIteratorIterator for recursive traversal
                default =>
                    $this->iteratorManager->RecursiveIteratorIterator(
                        $this->iteratorManager->RecursiveCallbackFilterIterator(
                            $this->iteratorManager->RecursiveDirectoryIterator($path, [
                                'flag' => ['skipDots' => true],
                                'mode' => ['asFileInfo' => true]
                            ]),
                            fn($fileInfo) => $this->applyFilter($fileInfo, $criteria)
                        ),
                        ['mode' => ['selfFirst' => true]]
                    )
            };
        } catch (Throwable $e) {
            throw new FinderException("Error configuring iterator: " . $e->getMessage());
        }
    }

    protected function applyFilter($fileInfo, array $criteria): bool
    {
        foreach ($criteria as $key => $value) {
            // Handle regular filterBy* methods
            $method = 'filterBy' . ucfirst($key);
            if (method_exists($this, $method) && !$this->$method($fileInfo, $value)) {
                return false;
            }

            // Handle pattern-based filterByPattern* methods if pattern criteria exist
            if (isset($criteria['pattern'][$key])) {
                $patternMethod = 'filterByPattern' . ucfirst($key);
                if (method_exists($this, $patternMethod) && !$this->$patternMethod($fileInfo, $criteria['pattern'][$key])) {
                    return false;
                }
            }
        }
        return true;
    }

    protected function applySort(array $items, array $sortCriteria = []): array
    {
        if (empty($sortCriteria)) {
            return $items;
        }

        try {
            usort($items, function ($a, $b) use ($sortCriteria) {
                foreach ($sortCriteria as $key => $order) {
                    $method = 'sortBy' . ucfirst($key);
                    if (method_exists($this, $method)) {
                        $comparison = $this->$method($a, $b);
                        if ($comparison !== 0) {
                            return ($order === 'asc') ? $comparison : -$comparison;
                        }
                    }
                }
                return 0;
            });
            return $items;
        } catch (Throwable $e) {
            throw new FinderException("Error during sorting: " . $e->getMessage());
        }
    }

    protected function validatePath(string $path): string
    {
        return $this->resolvePath($path);
    }

    protected function resolvePath(string $path): string
    {
        $fileInfo = $this->iteratorManager->FileInfo($path);

        if (!$fileInfo || !$fileInfo->getRealPath()) {
            throw new FinderException("Invalid path: $path");
        }

        if (!$fileInfo->isDir()) {
            throw new FinderException("Path is not a directory: $path");
        }

        return $fileInfo->getRealPath();
    }


    /**
      * Populates the cache with the entire directory and file structure under the root directory.
      */
      protected function populateCache(): void
      {
          try {
              // Initialize the RecursiveCachingIterator starting at the root
              $cachingIterator = $this->iteratorManager->RecursiveCachingIterator(
                  $this->iteratorManager->RecursiveDirectoryIterator($this->root, [
                      'flag' => ['skipDots' => true],
                      'mode' => ['asFileInfo' => true]
                  ])
              );

              // Set the iterator and start iteration
              $this->iteratorManager->setIterator($cachingIterator);
              $this->iteratorManager->rewind();

              while ($this->iteratorManager->valid()) {
                  // Access the current item from the iterator
                  $currentItem = $this->iteratorManager->current();

                  // Prepare children as an array of paths and names only, without `SplFileInfo` objects
                  $children = [];
                  if ($this->iteratorManager->hasChildren()) {
                      foreach ($this->iteratorManager->getChildren() as $child) {
                          $children[] = [
                              'path' => $child->getRealPath(),
                              'name' => $child->getFilename(),
                              'type' => $child->isDir() ? 'directory' : 'file',
                              'size' => $child->getSize(),
                              'permissions' => $child->getPerms()
                          ];
                      }
                  }

                  // Cache each item with full details and simplified children structure
                  $this->cache[] = [
                      'path' => $currentItem->getRealPath(),
                      'name' => $currentItem->getFilename(),
                      'type' => $currentItem->isDir() ? 'directory' : 'file',
                      'size' => $currentItem->getSize(),
                      'permissions' => $currentItem->getPerms(),
                      'depth' => $this->iteratorManager->getDepth(),
                      'children' => $children,
                  ];

                  $this->iteratorManager->next();
              }
          } catch (Throwable $e) {
              throw new FinderException("Error in populateCache: " . $e->getMessage(), 0, $e);
          }
      }
    protected function filterWithRegex(array $criteria, string $path): array
    {
        try {
            $this->iteratorManager->setIterator(
                $this->iteratorManager->RecursiveIteratorIterator(
                    $this->iteratorManager->RecursiveRegexIterator(
                        $this->iteratorManager->RecursiveDirectoryIterator($path, ['flag' => ['skipDots' => true]]),
                        $criteria['pattern'] ?? '/.*/'
                    ),
                    ['mode' => 'selfFirst']
                )
            );

            return $this->iteratorManager->toArray($this->iteratorManager->getIterator());
        } catch (Throwable $e) {
            throw new FinderException("Error during regex filtering: " . $e->getMessage());
        }
    }

    public function displayDirectoryTree(string $path): void
    {
        try {
            $this->iteratorManager->setIterator(
                $this->iteratorManager->RecursiveTreeIterator(
                    $this->iteratorManager->RecursiveIteratorIterator(
                        $this->iteratorManager->RecursiveDirectoryIterator($path, ['flag' => ['skipDots' => true]]),
                        ['mode' => 'selfFirst']
                    ),
                    ['flag' => ['bypassCurrent' => true]]
                )
            );

            foreach ($this->iteratorManager->getIterator() as $item) {
                echo $item . PHP_EOL;
            }
        } catch (Throwable $e) {
            throw new FinderException("Error displaying directory tree: " . $e->getMessage());
        }
    }

    public function searchMultipleDirectories(array $paths, array $criteria = [], array $sort = []): array
    {
        try {
            $this->iteratorManager->setIterator(
                $this->iteratorManager->RecursiveIteratorIterator(
                    $this->iteratorManager->RecursiveCallbackFilterIterator(
                        $this->iteratorManager->AppendIterator(
                            ...array_map(fn($path) => $this->iteratorManager->RecursiveDirectoryIterator($this->validatePath($path), ['flag' => ['skipDots' => true]]), $paths)
                        ),
                        fn($fileInfo) => $this->applyFilter($fileInfo, $criteria)
                    ),
                    ['mode' => 'selfFirst']
                )
            );

            return !empty($sort)
                ? $this->applySort($this->iteratorManager->toArray($this->iteratorManager->getIterator()), $sort)
                : $this->iteratorManager->toArray($this->iteratorManager->getIterator());
        } catch (Throwable $e) {
            throw new FinderException("Error during multi-directory search: " . $e->getMessage());
        }
    }

    protected function filterWithDepthControl(array $criteria, string $path, int $maxDepth, array $sort = []): array
    {
        try {
            $this->iteratorManager->setIterator(
                $this->iteratorManager->RecursiveIteratorIterator(
                    $this->iteratorManager->RecursiveCallbackFilterIterator(
                        $this->iteratorManager->RecursiveDirectoryIterator($path, ['flag' => ['skipDots' => true]]),
                        fn($fileInfo) => $this->applyFilter($fileInfo, $criteria)
                    ),
                    ['mode' => 'selfFirst', 'maxDepth' => $maxDepth]
                )
            );

            return !empty($sort)
                ? $this->applySort($this->iteratorManager->toArray($this->iteratorManager->getIterator()), $sort)
                : $this->iteratorManager->toArray($this->iteratorManager->getIterator());
        } catch (Throwable $e) {
            throw new FinderException("Error during depth-controlled filtering: " . $e->getMessage());
        }
    }

    public function customIterator(string $type, array $settings = [], array $criteria = [], array $sort = []): array
    {
        try {
            $this->iteratorManager->setIterator(
                $this->iteratorManager->CallbackFilterIterator(
                    $this->iteratorManager->createIterator($type, $settings),
                    fn($fileInfo) => $this->applyFilter($fileInfo, $criteria)
                )
            );

            return !empty($sort)
                ? $this->applySort($this->iteratorManager->toArray($this->iteratorManager->getIterator()), $sort)
                : $this->iteratorManager->toArray($this->iteratorManager->getIterator());
        } catch (Throwable $e) {
            throw new FinderException("Error during custom iterator resolution: " . $e->getMessage());
        }
    }

    abstract public function find(array $criteria = [], ?string $path = null, array $sort = []): array;
}
