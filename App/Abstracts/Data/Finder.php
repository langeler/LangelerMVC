<?php

namespace App\Abstracts\Data;

use App\Exceptions\Data\FinderException;
use App\Utilities\Managers\IteratorManager;
use App\Helpers\TypeChecker;
use App\Utilities\Sanitation\GeneralSanitizer;
use App\Utilities\Managers\ReflectionManager;
use SplFileInfo;

abstract class Finder
{
    protected ?string $root = '/';    // Default root directory is '/'
    protected ?string $path = null;   // Path for the current search
    protected IteratorManager $iteratorManager;
    protected TypeChecker $typeChecker;
    protected GeneralSanitizer $sanitizer;
    protected ReflectionManager $reflectionManager;

    /**
     * Constructor: Initialize dependencies and set root path dynamically.
     *
     * @param IteratorManager $iteratorManager Handles directory and file iteration
     * @param TypeChecker $typeChecker Validates types during runtime
     * @param GeneralSanitizer $sanitizer Sanitizes inputs like paths
     * @param ReflectionManager $reflectionManager Dynamically invokes filter methods
     */
    public function __construct(
        IteratorManager $iteratorManager,
        TypeChecker $typeChecker,
        GeneralSanitizer $sanitizer,
        ReflectionManager $reflectionManager
    ) {
        $this->iteratorManager = $iteratorManager;
        $this->typeChecker = $typeChecker;
        $this->sanitizer = $sanitizer;
        $this->reflectionManager = $reflectionManager;

        // Set root path dynamically
        $this->root = $this->setRoot();
    }

    /**
     * Abstract method to be implemented by extending classes.
     * Should return the filtered result of files or directories.
     *
     * @param array $criteria The criteria to filter files or directories
     * @param string|null $path Optional path to search within, defaults to root
     * @return array Filtered search results
     */
    abstract public function find(array $criteria = [], ?string $path = null): array;

    /**
     * Main function to handle filtering based on criteria and optional path.
     *
     * @param array $criteria Filtering criteria
     * @param string|null $path Optional directory path, defaults to root
     * @return array Filtered search results
     */
    public function handle(array $criteria = [], ?string $path = null): array
    {
        try {
            // Validate path and filter results
            $resolvedPath = $this->validatePath($path ?? $this->root);

            return $this->filter($criteria, $resolvedPath);
        } catch (\Exception $e) {
            throw new FinderException("Error handling path: " . $e->getMessage());
        }
    }

    /**
     * Filters the results using criteria and reflection-based methods.
     *
     * @param array $criteria The filtering criteria
     * @param string $path The directory path for the search
     * @return array Filtered results
     */
protected function filter(array $criteria, string $path): array
     {
         try {
             // Use the RecursiveDirectoryIterator to traverse all directories and subdirectories
             $iterator = $this->iteratorManager->RecursiveDirectory($path);

             // Wrap the iterator with RecursiveIteratorIterator to enable full recursion
             $recursiveIterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::SELF_FIRST);

             $filteredResults = [];

             foreach ($recursiveIterator as $fileInfo) {
                 // Skip '.' and '..'
                 if ($fileInfo->getFilename() === '.' || $fileInfo->getFilename() === '..') {
                     continue;
                 }

                 // Apply the filtering criteria to match specific files/folders
                 if ($this->matchesCriteria($fileInfo, $criteria)) {
                     $filteredResults[] = $fileInfo;
                 }
             }

             return $filteredResults;
         } catch (\Exception $e) {
             throw new FinderException("Error filtering path: " . $e->getMessage());
         }
     }

    /**
     * Filters the results by applying reflection-based filtering methods.
     *
     * @param SplFileInfo $fileInfo Current file or directory
     * @param array $criteria Filtering criteria
     * @return bool True if the file/directory matches the criteria, false otherwise
     */
    protected function matchesCriteria(SplFileInfo $fileInfo, array $criteria): bool
    {
        $methods = $this->getFilterMethods();

        foreach ($methods as $method) {
            if (!$this->callMethod($method, $fileInfo, $criteria)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get reflection-based methods that start with 'filterBy'.
     *
     * @return array An array of reflection-based filter methods
     */
    protected function getFilterMethods(): array
    {
        // Pass ReflectionClass instead of the object instance
        $reflectionClass = $this->reflectionManager->getClassInfo(get_class($this));
        return array_filter(
            $this->reflectionManager->getClassMethods($reflectionClass),
            fn($method) => strpos($method->getName(), 'filterBy') === 0
        );
    }

    /**
     * Call reflection-based filtering method.
     *
     * @param object $method Reflection method
     * @param object $data Current file/directory item
     * @param array $criteria Filtering criteria
     * @return bool True if the method passes, false otherwise
     */
protected function callMethod($method, SplFileInfo $fileInfo, array $criteria): bool
     {
         try {
             return method_exists($this, $method->getName())
                 ? $this->{$method->getName()}($fileInfo, $criteria)
                 : true;
         } catch (\Exception $e) {
             return false;
         }
     }

    /**
     * Set the root directory path.
     * Uses FilesystemIterator to determine the root path by looking for a known root marker (e.g., composer.json).
     *
     * @return string The determined root path
     * @throws FinderException If unable to determine a valid root
     */
    protected function setRoot(): string
    {
        $currentDir = __DIR__;
        $rootMarker = 'composer.json';

        while ($currentDir !== '/') {
            $iterator = $this->iteratorManager->Filesystem($currentDir);

            foreach ($iterator as $fileInfo) {
                if ($fileInfo->getFilename() === $rootMarker) {
                    return $currentDir;
                }
            }
            $currentDir = dirname($currentDir);
        }

        throw new FinderException("Unable to determine the application root. '$rootMarker' not found.");
    }

    /**
     * Validates and sanitizes the path to ensure it is within the root directory.
     *
     * @param string $path Path to sanitize
     * @return string The sanitized path
     * @throws FinderException If the path is invalid or outside the root directory
     */
protected function validatePath(string $path): string
     {
         $sanitizedPath = $this->sanitizer->sanitizeString($path);
         $realPath = realpath($sanitizedPath);

         if ($realPath !== false && strpos($realPath, $this->root) === 0) {
             return $realPath;
         }

         throw new FinderException("Invalid path: $sanitizedPath or outside root directory.");
     }
}
