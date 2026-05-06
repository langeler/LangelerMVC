<?php

declare(strict_types=1);

namespace App\Utilities\Managers\System;

use Throwable;                      // Base interface for all errors and exceptions in PHP.
use SplFileInfo;                    // Provides information about files.
use Iterator;                       // Interface for creating custom iterators.
use RecursiveIterator;              // Interface for recursive iteration.
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
use App\Utilities\Traits\{
    ArrayTrait,
    ExistenceCheckerTrait,
    TypeCheckerTrait
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
    use ArrayTrait, ExistenceCheckerTrait, TypeCheckerTrait;
    use IteratorTrait;
    use RecursiveIteratorTrait;

    /**
     * @var Iterator|null $iterator The current iterator being managed.
     */
    private ?Iterator $iterator = null;

    /**
     * Resolve the current iterator item as a SplFileInfo instance when available.
     *
     * This lets the manager expose file-oriented convenience methods even when the
     * active iterator is a wrapper such as RecursiveIteratorIterator.
     *
     * @return SplFileInfo|null
     */
    private function currentFileInfo(): ?SplFileInfo
    {
        $current = $this->iterator?->current();

        return $current instanceof SplFileInfo
            ? $current
            : null;
    }

    /**
     * Constructor.
     *
     * Initializes the iterator settings by invoking constructors from
     * the included traits (`IteratorTrait` and `RecursiveIteratorTrait`).
     */
    public function __construct()
    {
        $this->initializeIteratorTrait();
        $this->initializeRecursiveIteratorTrait();
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

        $resolved = [];

        foreach (['flag', 'mode', 'prefix', 'cache'] as $group) {
            if (!isset($overrides[$group])) {
                continue;
            }

            $overrideGroup = $overrides[$group];

            if ($this->isInt($overrideGroup)) {
                $resolved[$group] = $overrideGroup;
                continue;
            }

            if (!$this->isArray($overrideGroup)) {
                continue;
            }

            $defaultGroup = $defaultSettings[$group] ?? [];
            $selectedFlags = [];

            foreach ($overrideGroup as $key => $value) {
                if ($this->isInt($key) && $this->isInt($value)) {
                    $selectedFlags[] = $value;
                    continue;
                }

                if ($value && isset($defaultGroup[$key])) {
                    $selectedFlags[] = $defaultGroup[$key];
                }
            }

            if ($selectedFlags !== []) {
                $resolved[$group] = $this->combineFlags([], $selectedFlags);
            }
        }

        if (isset($overrides['maxDepth']) && $this->isInt($overrides['maxDepth'])) {
            $resolved['maxDepth'] = $overrides['maxDepth'];
        }

        return $resolved;
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
        return $this->reduce(
            [...$this->getValues($defaultFlags), ...$this->getValues($overrideFlags)],
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
    public function createIterator(string $iteratorName, array $settings = [], ...$args): Iterator
    {
        try {
            $iterator = null;

            if ($this->methodExists($this, $iteratorName)) {
                $reflectionMethod = new \ReflectionMethod($this, $iteratorName);
                $parameters = $reflectionMethod->getParameters();
                $expectsSettings = $parameters !== []
                    && (($type = $parameters[array_key_last($parameters)]->getType()) instanceof \ReflectionNamedType)
                    && $type->getName() === 'array';
                $iterator = $this->{$iteratorName}(
                    ...($settings !== [] && $expectsSettings
                        ? $this->merge($args, [$settings])
                        : $args)
                );
            } else {
                if ($settings !== []) {
                    throw new IteratorException("Iterator '{$iteratorName}' does not support manager-level settings.");
                }

                $iteratorClass = $this->resolve($iteratorName);
                $iterator = new $iteratorClass(...$args);
            }

            $this->iterator = $iterator;
            return $iterator;
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
        if ($this->iterator !== null && $this->methodExists($this->iterator, 'previous')) {
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
        return match (true) {
            $this->iterator instanceof RecursiveIteratorIterator => $this->iterator->callHasChildren(),
            $this->iterator instanceof RecursiveIterator => $this->iterator->hasChildren(),
            default => false,
        };
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
        return $this->currentFileInfo()?->getPerms() ?? 0;
    }

    /**
     * Get the size of the current file (for RecursiveDirectoryIterator).
     *
     * @return int The file size or 0 if not applicable.
     */
    public function getSize(): int
    {
        return $this->currentFileInfo()?->getSize() ?? 0;
    }

    /**
     * Get the real path of the current file (for RecursiveDirectoryIterator).
     *
     * @return string The real path or an empty string if not applicable.
     */
    public function getRealPath(): string
    {
        return $this->currentFileInfo()?->getRealPath() ?: '';
    }

    /**
     * Check if the current element is a file (for RecursiveDirectoryIterator).
     *
     * @return bool True if it is a file, otherwise false.
     */
    public function isFile(): bool
    {
        return $this->currentFileInfo()?->isFile() ?? false;
    }

    /**
     * Check if the current element is a directory (for RecursiveDirectoryIterator).
     *
     * @return bool True if it is a directory, otherwise false.
     */
    public function isDir(): bool
    {
        return $this->currentFileInfo()?->isDir() ?? false;
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
        if ($iterator === null) {
            return 0;
        }

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
        if ($iterator === null) {
            return [];
        }

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
        if ($iterator === null) {
            return 0;
        }

        return iterator_count($iterator);
    }
}
