<?php

namespace App\Utilities\Managers;

use IteratorIterator;
use ArrayIterator;
use CachingIterator;
use FilterIterator;
use LimitIterator;
use NoRewindIterator;
use SeekableIterator;
use RecursiveIteratorIterator;
use RecursiveArrayIterator;
use RecursiveDirectoryIterator;
use RecursiveFilterIterator;
use RecursiveCallbackFilterIterator;
use RecursiveCachingIterator;
use RecursiveRegexIterator;
use RecursiveTreeIterator;
use DirectoryIterator;
use FilesystemIterator;
use GlobIterator;
use RegexIterator;

/**
 * Class IteratorManager
 *
 * Provides utility methods for working with both recursive and non-recursive iterators, including regex filtering.
 */
class IteratorManager
{
	// Non-Recursive Iterator Methods

	/**
	 * Create an ArrayIterator from an array.
	 *
	 * @param array $array The input array.
	 * @return ArrayIterator The ArrayIterator instance.
	 */
	public function createArrayIterator(array $array): ArrayIterator
	{
		return new ArrayIterator($array);
	}

	/**
	 * Flatten a nested iterator into a single level.
	 *
	 * @param \Traversable $iterator The nested iterator.
	 * @return IteratorIterator The flattened iterator.
	 */
	public function flattenIterator(\Traversable $iterator): IteratorIterator
	{
		return new IteratorIterator($iterator);
	}

	/**
	 * Create a CachingIterator to cache iterator values.
	 *
	 * @param \Traversable $iterator The input iterator.
	 * @return CachingIterator The caching iterator.
	 */
	public function createCachingIterator(\Traversable $iterator): CachingIterator
	{
		return new CachingIterator($iterator);
	}

	/**
	 * Create a FilterIterator that filters elements based on a callback.
	 *
	 * @param \Traversable $iterator The input iterator.
	 * @param callable $filterCallback The callback to filter elements.
	 * @return FilterIterator The filtered iterator.
	 */
	public function createFilterIterator(\Traversable $iterator, callable $filterCallback): FilterIterator
	{
		return new class($iterator, $filterCallback) extends FilterIterator {
			private $callback;

			public function __construct($iterator, $callback)
			{
				parent::__construct($iterator);
				$this->callback = $callback;
			}

			public function accept()
			{
				return call_user_func($this->callback, $this->current());
			}
		};
	}

	/**
	 * Create a LimitIterator that restricts the number of elements iterated.
	 *
	 * @param \Traversable $iterator The input iterator.
	 * @param int $offset The starting offset.
	 * @param int $count The maximum number of elements to iterate.
	 * @return LimitIterator The limited iterator.
	 */
	public function createLimitIterator(\Traversable $iterator, int $offset, int $count): LimitIterator
	{
		return new LimitIterator($iterator, $offset, $count);
	}

	/**
	 * Create a NoRewindIterator to prevent the iterator from being rewound.
	 *
	 * @param \Traversable $iterator The input iterator.
	 * @return NoRewindIterator The NoRewindIterator instance.
	 */
	public function createNoRewindIterator(\Traversable $iterator): NoRewindIterator
	{
		return new NoRewindIterator($iterator);
	}

	/**
	 * Seek to a specific position in a SeekableIterator.
	 *
	 * @param SeekableIterator $iterator The SeekableIterator instance.
	 * @param int $position The position to seek to.
	 * @return void
	 */
	public function seek(SeekableIterator $iterator, int $position): void
	{
		$iterator->seek($position);
	}

	// Recursive Iterator Methods

	/**
	 * Create a RecursiveArrayIterator for traversing a multidimensional array.
	 *
	 * @param array $array The input array.
	 * @return RecursiveArrayIterator The RecursiveArrayIterator instance.
	 */
	public function createRecursiveArrayIterator(array $array): RecursiveArrayIterator
	{
		return new RecursiveArrayIterator($array);
	}

	/**
	 * Create a RecursiveDirectoryIterator for traversing directories.
	 *
	 * @param string $path The directory path.
	 * @param int $flags Optional. The flags to use (default: KEY_AS_PATHNAME | CURRENT_AS_FILEINFO).
	 * @return RecursiveDirectoryIterator The RecursiveDirectoryIterator instance.
	 */
	public function createRecursiveDirectoryIterator(string $path, int $flags = FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO): RecursiveDirectoryIterator
	{
		return new RecursiveDirectoryIterator($path, $flags);
	}

	/**
	 * Create a RecursiveIterator for traversing recursively through elements.
	 *
	 * @param RecursiveIteratorIterator $iterator The recursive iterator.
	 * @return RecursiveIteratorIterator The RecursiveIteratorIterator instance.
	 */
	public function createRecursiveIterator(RecursiveIteratorIterator $iterator): RecursiveIteratorIterator
	{
		return new RecursiveIteratorIterator($iterator);
	}

	/**
	 * Create a RecursiveCallbackFilterIterator with a filtering callback.
	 *
	 * @param RecursiveIteratorIterator $iterator The recursive iterator.
	 * @param callable $callback The callback function for filtering.
	 * @return RecursiveCallbackFilterIterator The filtered recursive iterator.
	 */
	public function createRecursiveCallbackFilterIterator(RecursiveIteratorIterator $iterator, callable $callback): RecursiveCallbackFilterIterator
	{
		return new RecursiveCallbackFilterIterator($iterator, $callback);
	}

	/**
	 * Create a RecursiveCachingIterator that caches child elements.
	 *
	 * @param RecursiveIteratorIterator $iterator The recursive iterator.
	 * @param int $flags Optional. The caching flags (default: CATCH_GET_CHILD).
	 * @return RecursiveCachingIterator The RecursiveCachingIterator instance.
	 */
	public function createRecursiveCachingIterator(RecursiveIteratorIterator $iterator, int $flags = RecursiveCachingIterator::CATCH_GET_CHILD): RecursiveCachingIterator
	{
		return new RecursiveCachingIterator($iterator, $flags);
	}

	/**
	 * Create a RecursiveRegexIterator for filtering based on a regex pattern.
	 *
	 * @param RecursiveIteratorIterator $iterator The recursive iterator.
	 * @param string $pattern The regex pattern to filter by.
	 * @return RecursiveRegexIterator The RecursiveRegexIterator instance.
	 */
	public function createRecursiveRegexIterator(RecursiveIteratorIterator $iterator, string $pattern): RecursiveRegexIterator
	{
		return new RecursiveRegexIterator($iterator, $pattern);
	}

	/**
	 * Create a RecursiveTreeIterator for traversing hierarchical structures.
	 *
	 * @param RecursiveIterator $iterator The recursive iterator.
	 * @return RecursiveTreeIterator The RecursiveTreeIterator instance.
	 */
	public function createRecursiveTreeIterator(RecursiveIterator $iterator): RecursiveTreeIterator
	{
		return new RecursiveTreeIterator($iterator);
	}

	// DirectoryIterator Methods

	/**
	 * Create a DirectoryIterator for traversing files in a directory.
	 *
	 * @param string $directory The directory path.
	 * @return DirectoryIterator The DirectoryIterator instance.
	 */
	public function createDirectoryIterator(string $directory): DirectoryIterator
	{
		return new DirectoryIterator($directory);
	}

	/**
	 * Get the name of the current file or directory item.
	 *
	 * @param DirectoryIterator $iterator The DirectoryIterator instance.
	 * @return string The name of the file or directory item.
	 */
	public function getDirectoryItemName(DirectoryIterator $iterator): string
	{
		return $iterator->getFilename();
	}

	// FilesystemIterator Methods

	/**
	 * Create a FilesystemIterator for traversing file system items.
	 *
	 * @param string $directory The directory path.
	 * @param int $flags Optional. The flags to use (default: KEY_AS_PATHNAME).
	 * @return FilesystemIterator The FilesystemIterator instance.
	 */
	public function createFilesystemIterator(string $directory, int $flags = FilesystemIterator::KEY_AS_PATHNAME): FilesystemIterator
	{
		return new FilesystemIterator($directory, $flags);
	}

	// GlobIterator Methods

	/**
	 * Create a GlobIterator for matching files using a pattern.
	 *
	 * @param string $pattern The glob pattern.
	 * @return GlobIterator The GlobIterator instance.
	 */
	public function createGlobIterator(string $pattern): GlobIterator
	{
		return new GlobIterator($pattern);
	}

	// Regex Iterator Methods

	/**
	 * Create a RegexIterator to filter elements based on a regular expression.
	 *
	 * @param \Iterator $iterator The input iterator.
	 * @param string $pattern The regex pattern.
	 * @param int $mode Optional. The match mode (default: MATCH).
	 * @param int $flags Optional. The flags to use (default: 0).
	 * @param int $pregFlags Optional. The preg_match flags (default: 0).
	 * @return RegexIterator The RegexIterator instance.
	 */
	public function createRegexIterator(\Iterator $iterator, string $pattern, int $mode = RegexIterator::MATCH, int $flags = 0, int $pregFlags = 0): RegexIterator
	{
		return new RegexIterator($iterator, $pattern, $mode, $flags, $pregFlags);
	}

	/**
	 * Get the current mode of the RegexIterator.
	 *
	 * @param RegexIterator $iterator The RegexIterator instance.
	 * @return int The current mode of the iterator.
	 */
	public function getRegexIteratorMode(RegexIterator $iterator): int
	{
		return $iterator->getMode();
	}

	/**
	 * Set the mode of the RegexIterator.
	 *
	 * @param RegexIterator $iterator The RegexIterator instance.
	 * @param int $mode The mode to set.
	 * @return void
	 */
	public function setRegexIteratorMode(RegexIterator $iterator, int $mode): void
	{
		$iterator->setMode($mode);
	}

	/**
	 * Filter an iterator by a regex pattern and return the results as an array.
	 *
	 * @param \Iterator $iterator The input iterator.
	 * @param string $pattern The regex pattern to filter by.
	 * @return array The filtered elements.
	 */
	public function filterByRegex(\Iterator $iterator, string $pattern): array
	{
		$regexIterator = $this->createRegexIterator($iterator, $pattern);
		return iterator_to_array($regexIterator);
	}
}
