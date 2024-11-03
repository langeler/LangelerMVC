<?php

namespace App\Utilities\Managers;

use App\Exceptions\Iterator\IteratorException;
use App\Exceptions\Iterator\IteratorNotFoundException;
use Throwable;
use SplFileInfo;
use Iterator;
use RecursiveIterator;
use Traversable;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use App\Utilities\Traits\Iterator\IteratorTrait;
use App\Utilities\Traits\Iterator\RecursiveIteratorTrait;

class IteratorManager
{
    use IteratorTrait {
        IteratorTrait::__construct as iteratorTraitConstruct;
    }

    use RecursiveIteratorTrait {
        RecursiveIteratorTrait::__construct as recursiveIteratorTraitConstruct;
    }

    private ?Iterator $iterator = null;

    public function __construct()
    {
        $this->iteratorTraitConstruct();
        $this->recursiveIteratorTraitConstruct();
    }

    public function setIterator(?Iterator $iterator): void
    {
        $this->iterator = $iterator;
    }

    public function getIterator(): ?Iterator
    {
        return $this->iterator;
    }

private function fetchSettings(string $iterator, array $overrides = []): array
    {
        // Retrieve default settings from iterator settings or recursive iterator settings
        $defaultSettings = $this->iteratorSettings[$iterator]
            ?? $this->recursiveIteratorSettings[$iterator]
            ?? throw new IteratorNotFoundException("Settings for iterator '{$iterator}' not found.");

        // Define allowed setting keys
        $allowedKeys = ['flag', 'mode', 'prefix', 'cache'];

        // Merge settings with overrides, handling bitwise combination for flag/mode values
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
     */
    private function combineFlags(array $defaultFlags, array $overrideFlags): int
    {
        // Use bitwise OR to combine flags
        return array_reduce(
            [...array_values($defaultFlags), ...array_values($overrideFlags)],
            fn($carry, $flag) => $carry | $flag,
            0
        );
    }

    private function resolve(string $iterator): string
    {
        // Resolve the iterator class from settings (standard or recursive)
        return $this->iteratorSettings[$iterator]['class']
            ?? $this->recursiveIteratorSettings[$iterator]['class']
            ?? throw new IteratorNotFoundException("Unknown iterator: $iterator");
    }

    private function createIterator(string $iteratorName, array $settings = [], ...$args): Iterator
    {
        try {
            // Resolve the iterator class and fetch settings
            $iteratorClass = $this->resolve($iteratorName);
            $settingsValues = $this->fetchSettings($iteratorName, $settings);

            // Create the iterator instance with the resolved class and settings
            return new $iteratorClass(...$args);
        } catch (Throwable $e) {
            throw new IteratorException("Error creating {$iteratorName}: " . $e->getMessage(), 0, $e);
        }
    }

    public function current(): mixed
    {
        // Return the current element from the iterator, if set
        return $this->iterator?->current();
    }

    public function next(): void
    {
        // Move to the next element in the iterator
        $this->iterator?->next();
    }

    public function previous(): void
    {
        // Check if the iterator supports 'previous' and invoke it, else throw an exception
        if (method_exists($this->iterator, 'previous')) {
            $this->iterator->previous();
        } else {
            throw new IteratorException("Previous operation not supported by this iterator.");
        }
    }

    public function rewind(): void
    {
        // Rewind the iterator back to the first element
        $this->iterator?->rewind();
    }

    public function key(): mixed
    {
        // Return the current key in the iterator
        return $this->iterator?->key();
    }

    public function valid(): bool
    {
        // Check if the current iterator position is valid
        return $this->iterator?->valid() ?? false;
    }

    public function getDepth(): int
    {
        // Return the current depth if using a RecursiveIteratorIterator, else return 0
        return $this->iterator instanceof RecursiveIteratorIterator
            ? $this->iterator->getDepth()
            : 0;
    }

    public function hasChildren(): bool
    {
        // Check if the current element has children (for RecursiveIterator)
        return $this->iterator instanceof RecursiveIterator && $this->iterator->hasChildren();
    }

    public function getChildren(): ?RecursiveIterator
    {
        // Get the children of the current element (for RecursiveIterator)
        return $this->iterator instanceof RecursiveIterator
            ? $this->iterator->getChildren()
            : null;
    }

    public function getPermissions(): int
    {
        // Get the permissions of the current file (for RecursiveDirectoryIterator)
        return $this->iterator instanceof RecursiveDirectoryIterator
            ? $this->iterator->current()->getPerms()
            : 0;
    }

    public function getSize(): int
    {
        // Get the size of the current file (for RecursiveDirectoryIterator)
        return $this->iterator instanceof RecursiveDirectoryIterator
            ? $this->iterator->current()->getSize()
            : 0;
    }

    public function getRealPath(): string
    {
        // Get the real path of the current file (for RecursiveDirectoryIterator)
        return $this->iterator instanceof RecursiveDirectoryIterator
            ? $this->iterator->current()->getRealPath()
            : '';
    }

    public function isFile(): bool
    {
        // Check if the current element is a file (for RecursiveDirectoryIterator)
        return $this->iterator instanceof RecursiveDirectoryIterator
            ? $this->iterator->current()->isFile()
            : false;
    }

    public function isDir(): bool
    {
        // Check if the current element is a directory (for RecursiveDirectoryIterator)
        return $this->iterator instanceof RecursiveDirectoryIterator
            ? $this->iterator->current()->isDir()
            : false;
    }

    public function FileInfo(string $filePath): SplFileInfo
    {
        // Return a SplFileInfo object for the provided file path
        return new SplFileInfo($filePath);
    }

    public function applyCallback(?Iterator $iterator, callable $callback): int
    {
        // Apply the provided callback function to the iterator
        return iterator_apply($iterator, $callback);
    }

    public function toArray(?Iterator $iterator, bool $useKeys = true): array
    {
        // Convert the iterator to an array
        return iterator_to_array($iterator, $useKeys);
    }

    public function count(?Iterator $iterator): int
    {
        // Count the elements in the iterator
        return iterator_count($iterator);
    }
}
