<?php

namespace App\Utilities\Managers;

use Throwable;                      // Base interface for all errors and exceptions in PHP.
use SplFileInfo;                    // Provides information about files.
use Iterator;                       // Interface for creating custom iterators.
use RecursiveIterator;              // Interface for recursive iteration.
use Traversable;                    // Base interface for objects used in iteration.
use RecursiveDirectoryIterator;     // Iterator for directories, allowing recursive traversal.
use RecursiveIteratorIterator;      // Iterator for flattening recursive iterators.

use App\Exceptions\Iterator\{
    IteratorException,              // Exception for general iterator-related errors.
    IteratorNotFoundException       // Exception for cases where an iterator is not found.
};

use App\Utilities\Traits\Iterator\{
    IteratorTrait,                  // Provides utility methods for working with iterators.
    RecursiveIteratorTrait          // Adds functionality for recursive iteration handling.
};

/**
 * Class IteratorManager
 *
 * Provides a unified interface for managing and utilizing standard and recursive iterators.
 * This class integrates functionality from the `IteratorTrait` and `RecursiveIteratorTrait`
 * to create, configure, and operate on various iterator types.
 *
 * It supports both standard iterators like `ArrayIterator` and recursive iterators
 * like `RecursiveDirectoryIterator`, along with helper methods to manipulate and inspect iterators.
 */
class IteratorManager
{
    use IteratorTrait {
        IteratorTrait::__construct as iteratorTraitConstruct;
    }

    use RecursiveIteratorTrait {
        RecursiveIteratorTrait::__construct as recursiveIteratorTraitConstruct;
    }

    /**
     * @var Iterator|null $iterator The current iterator being managed.
     */
    private ?Iterator $iterator = null;

    /**
     * Constructor.
     *
     * Initializes the iterator settings by invoking constructors from
     * the included traits (`IteratorTrait` and `RecursiveIteratorTrait`).
     */
    public function __construct()
    {
        $this->iteratorTraitConstruct();
        $this->recursiveIteratorTraitConstruct();
    }

    /**
     * Set the current iterator.
     *
     * @param Iterator|null $iterator The iterator to set.
     */
    public function setIterator(?Iterator $iterator): void
    {
        $this->iterator = $iterator;
    }

    /**
     * Get the current iterator.
     *
     * @return Iterator|null The current iterator or null if none is set.
     */
    public function getIterator(): ?Iterator
    {
        return $this->iterator;
    }

    /**
     * Fetch settings for a given iterator.
     *
     * Combines default settings from iterator traits with overrides.
     *
     * @param string $iterator The name of the iterator.
     * @param array $overrides Custom settings to override defaults.
     * @return array The combined settings.
     * @throws IteratorNotFoundException If the iterator settings are not found.
     */
    private function fetchSettings(string $iterator, array $overrides = []): array
    {
        $defaultSettings = $this->iteratorSettings[$iterator]
            ?? $this->recursiveIteratorSettings[$iterator]
            ?? throw new IteratorNotFoundException("Settings for iterator '{$iterator}' not found.");

        $allowedKeys = ['flag', 'mode', 'prefix', 'cache'];

        return array_filter(
            array_map(
                fn($key) => isset($overrides[$key])
                    ? $this->combineFlags($defaultSettings[$key] ?? [], $overrides[$key])
                    : ($defaultSettings[$key] ?? null),
                array_flip($allowedKeys)
            ),
            fn($value) => $value !== null
        );
    }

    /**
     * Combine constants using bitwise OR for flag/mode settings.
     *
     * @param array $defaultFlags Default flags.
     * @param array $overrideFlags Flags to override.
     * @return int The combined flags.
     */
    private function combineFlags(array $defaultFlags, array $overrideFlags): int
    {
        return array_reduce(
            [...array_values($defaultFlags), ...array_values($overrideFlags)],
            fn($carry, $flag) => $carry | $flag,
            0
        );
    }

    /**
     * Resolve the class name for a given iterator.
     *
     * @param string $iterator The name of the iterator.
     * @return string The fully qualified class name.
     * @throws IteratorNotFoundException If the iterator class is not found.
     */
    private function resolve(string $iterator): string
    {
        return $this->iteratorSettings[$iterator]['class']
            ?? $this->recursiveIteratorSettings[$iterator]['class']
            ?? throw new IteratorNotFoundException("Unknown iterator: $iterator");
    }

    /**
     * Create an iterator instance.
     *
     * @param string $iteratorName The name of the iterator to create.
     * @param array $settings Custom settings for the iterator.
     * @param mixed ...$args Additional arguments for the iterator constructor.
     * @return Iterator The created iterator instance.
     * @throws IteratorException If the iterator cannot be created.
     */
    private function createIterator(string $iteratorName, array $settings = [], ...$args): Iterator
    {
        try {
            $iteratorClass = $this->resolve($iteratorName);
            $settingsValues = $this->fetchSettings($iteratorName, $settings);
            return new $iteratorClass(...$args);
        } catch (Throwable $e) {
            throw new IteratorException("Error creating {$iteratorName}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the current element in the iterator.
     *
     * @return mixed The current element or null if the iterator is not set.
     */
    public function current(): mixed
    {
        return $this->iterator?->current();
    }

    /**
     * Move to the next element in the iterator.
     */
    public function next(): void
    {
        $this->iterator?->next();
    }

    /**
     * Move to the previous element in the iterator.
     *
     * @throws IteratorException If the iterator does not support `previous`.
     */
    public function previous(): void
    {
        if (method_exists($this->iterator, 'previous')) {
            $this->iterator->previous();
        } else {
            throw new IteratorException("Previous operation not supported by this iterator.");
        }
    }

    /**
     * Rewind the iterator to the first element.
     */
    public function rewind(): void
    {
        $this->iterator?->rewind();
    }

    /**
     * Get the current key in the iterator.
     *
     * @return mixed The current key or null if the iterator is not set.
     */
    public function key(): mixed
    {
        return $this->iterator?->key();
    }

    /**
     * Check if the current iterator position is valid.
     *
     * @return bool True if the position is valid, otherwise false.
     */
    public function valid(): bool
    {
        return $this->iterator?->valid() ?? false;
    }

    /**
     * Get the current depth in a RecursiveIterator.
     *
     * @return int The depth or 0 if not a RecursiveIterator.
     */
    public function getDepth(): int
    {
        return $this->iterator instanceof RecursiveIteratorIterator
            ? $this->iterator->getDepth()
            : 0;
    }

    /**
     * Check if the current element has children (for RecursiveIterator).
     *
     * @return bool True if the element has children, otherwise false.
     */
    public function hasChildren(): bool
    {
        return $this->iterator instanceof RecursiveIterator && $this->iterator->hasChildren();
    }

    /**
     * Get the children of the current element (for RecursiveIterator).
     *
     * @return RecursiveIterator|null The children iterator or null if not applicable.
     */
    public function getChildren(): ?RecursiveIterator
    {
        return $this->iterator instanceof RecursiveIterator
            ? $this->iterator->getChildren()
            : null;
    }

    /**
     * Get the permissions of the current file (for RecursiveDirectoryIterator).
     *
     * @return int The file permissions or 0 if not applicable.
     */
    public function getPermissions(): int
    {
        return $this->iterator instanceof RecursiveDirectoryIterator
            ? $this->iterator->current()->getPerms()
            : 0;
    }

    /**
     * Get the size of the current file (for RecursiveDirectoryIterator).
     *
     * @return int The file size or 0 if not applicable.
     */
    public function getSize(): int
    {
        return $this->iterator instanceof RecursiveDirectoryIterator
            ? $this->iterator->current()->getSize()
            : 0;
    }

    /**
     * Get the real path of the current file (for RecursiveDirectoryIterator).
     *
     * @return string The real path or an empty string if not applicable.
     */
    public function getRealPath(): string
    {
        return $this->iterator instanceof RecursiveDirectoryIterator
            ? $this->iterator->current()->getRealPath()
            : '';
    }

    /**
     * Check if the current element is a file (for RecursiveDirectoryIterator).
     *
     * @return bool True if it is a file, otherwise false.
     */
    public function isFile(): bool
    {
        return $this->iterator instanceof RecursiveDirectoryIterator
            ? $this->iterator->current()->isFile()
            : false;
    }

    /**
     * Check if the current element is a directory (for RecursiveDirectoryIterator).
     *
     * @return bool True if it is a directory, otherwise false.
     */
    public function isDir(): bool
    {
        return $this->iterator instanceof RecursiveDirectoryIterator
            ? $this->iterator->current()->isDir()
            : false;
    }

    /**
     * Create a SplFileInfo object for a given file path.
     *
     * @param string $filePath The file path.
     * @return SplFileInfo The SplFileInfo object.
     */
    public function FileInfo(string $filePath): SplFileInfo
    {
        return new SplFileInfo($filePath);
    }

    /**
     * Apply a callback to the elements of an iterator.
     *
     * @param Iterator|null $iterator The iterator to apply the callback to.
     * @param callable $callback The callback function.
     * @return int The number of elements processed.
     */
    public function applyCallback(?Iterator $iterator, callable $callback): int
    {
        return iterator_apply($iterator, $callback);
    }

    /**
     * Convert an iterator to an array.
     *
     * @param Iterator|null $iterator The iterator to convert.
     * @param bool $useKeys Whether to preserve keys in the array.
     * @return array The converted array.
     */
    public function toArray(?Iterator $iterator, bool $useKeys = true): array
    {
        return iterator_to_array($iterator, $useKeys);
    }

    /**
     * Count the number of elements in an iterator.
     *
     * @param Iterator|null $iterator The iterator to count.
     * @return int The number of elements.
     */
    public function count(?Iterator $iterator): int
    {
        return iterator_count($iterator);
    }
}
