<?php

namespace App\Utilities\Managers;

use ArrayIterator;
use AppendIterator;
use CallbackFilterIterator;
use CachingIterator;
use DirectoryIterator;
use EmptyIterator;
use FilesystemIterator;
use FilterIterator;
use GlobIterator;
use InfiniteIterator;
use IteratorIterator;
use LimitIterator;
use MultipleIterator;
use NoRewindIterator;
use ParentIterator;
use RecursiveArrayIterator;
use RecursiveCallbackFilterIterator;
use RecursiveCachingIterator;
use RecursiveDirectoryIterator;
use RecursiveFilterIterator;
use RecursiveIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RecursiveTreeIterator;
use RegexIterator;
use SeekableIterator;

/**
 * Class IteratorManager
 *
 * A utility class for creating, managing, and configuring various PHP SPL iterators.
 * Provides methods for non-recursive and recursive iterators, and advanced filtering via regex and callbacks.
 */
class IteratorManager
{
	/**
	 * Mapping of human-readable mode keys to RegexIterator constants.
	 * This allows users to easily set RegexIterator modes with intuitive keys (e.g., 'match', 'replace').
	 *
	 * @var array
	 */
	protected $regexModes = [
		'modes' => [
			'match' => RegexIterator::MATCH,
			'getMatch' => RegexIterator::GET_MATCH,
			'allMatches' => RegexIterator::ALL_MATCHES,
			'split' => RegexIterator::SPLIT,
			'replace' => RegexIterator::REPLACE
		]
	];

	/**
	 * Get the current mode of the RegexIterator as a human-readable key.
	 *
	 * @param RegexIterator $iterator The RegexIterator instance.
	 * @return string|null The mode key (e.g., 'match', 'replace') or null if not found.
	 */
	public function getRegExMode(RegexIterator $iterator): ?string
	{
		$currentMode = $iterator->getMode();
		return array_search($currentMode, $this->regexModes['modes'], true) ?: null;
	}

	/**
	 * Set the mode of the RegexIterator using a human-readable mode key.
	 *
	 * @param RegexIterator $iterator The RegexIterator instance.
	 * @param string $modeKey The mode key ('match', 'getMatch', etc.).
	 * @return bool Returns true if the mode was set successfully, false otherwise.
	 */
	public function setRegExMode(RegexIterator $iterator, string $modeKey): bool
	{
		if (isset($this->regexModes['modes'][$modeKey])) {
			$iterator->setMode($this->regexModes['modes'][$modeKey]);
			return true;
		}
		return false;
	}

	// Non-Recursive Iterators

	/**
	 * Create an ArrayIterator from an array.
	 *
	 * @param array $array The input array.
	 * @return ArrayIterator The ArrayIterator instance.
	 */
	public function Array(array $array): ArrayIterator
	{
		return new ArrayIterator($array);
	}

	/**
	 * Create an AppendIterator to append multiple iterators together.
	 *
	 * @param array $iterators An array of iterators to append.
	 * @return AppendIterator The AppendIterator instance.
	 */
	public function Append(array $iterators): AppendIterator
	{
		$appendIterator = new AppendIterator();
		foreach ($iterators as $iterator) {
			$appendIterator->append($iterator);
		}
		return $appendIterator;
	}

	/**
	 * Create a CallbackFilterIterator for filtering elements based on a callback function.
	 *
	 * @param \Iterator $iterator The input iterator.
	 * @param callable $callback The filtering callback.
	 * @return CallbackFilterIterator The CallbackFilterIterator instance.
	 */
	public function callbackFilter(\Iterator $iterator, callable $callback): CallbackFilterIterator
	{
		return new CallbackFilterIterator($iterator, $callback);
	}

	/**
	 * Create a CachingIterator to cache iterator values.
	 *
	 * @param \Traversable $iterator The input iterator.
	 * @return CachingIterator The CachingIterator instance.
	 */
	public function Caching(\Traversable $iterator): CachingIterator
	{
		return new CachingIterator($iterator);
	}

	/**
	 * Create a DirectoryIterator to iterate over a directory's contents.
	 *
	 * @param string $directory The directory path.
	 * @return DirectoryIterator The DirectoryIterator instance.
	 */
	public function Directory(string $directory): DirectoryIterator
	{
		return new DirectoryIterator($directory);
	}

	/**
	 * Get the name of the current file or directory in a DirectoryIterator.
	 *
	 * @param DirectoryIterator $iterator The DirectoryIterator instance.
	 * @return string The name of the current file or directory.
	 */
	public function dirItemName(DirectoryIterator $iterator): string
	{
		return $iterator->getFilename();
	}

	/**
	 * Create an EmptyIterator, which contains no elements.
	 *
	 * @return EmptyIterator The EmptyIterator instance.
	 */
	public function Empty(): EmptyIterator
	{
		return new EmptyIterator();
	}

	/**
	 * Create a FilesystemIterator to iterate over a directory's contents.
	 *
	 * @param string $directory The directory path.
	 * @param int $flags Optional flags (default: KEY_AS_PATHNAME).
	 * @return FilesystemIterator The FilesystemIterator instance.
	 */
	public function Filesystem(string $directory, int $flags = FilesystemIterator::KEY_AS_PATHNAME): FilesystemIterator
	{
		return new FilesystemIterator($directory, $flags);
	}

	/**
	 * Create a FilterIterator to filter elements based on a callback.
	 *
	 * @param \Traversable $iterator The input iterator.
	 * @param callable $callback The callback to filter elements.
	 * @return FilterIterator The filtered iterator.
	 */
	public function Filter(\Traversable $iterator, callable $callback): FilterIterator
	{
		return new class($iterator, $callback) extends FilterIterator {
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
	 * Create a GlobIterator for matching files using a glob pattern.
	 *
	 * @param string $pattern The glob pattern.
	 * @return GlobIterator The GlobIterator instance.
	 */
	public function Glob(string $pattern): GlobIterator
	{
		return new GlobIterator($pattern);
	}

	/**
	 * Create an InfiniteIterator that endlessly loops over the input iterator.
	 *
	 * @param \Iterator $iterator The input iterator.
	 * @return InfiniteIterator The InfiniteIterator instance.
	 */
	public function Infinite(\Iterator $iterator): InfiniteIterator
	{
		return new InfiniteIterator($iterator);
	}

	/**
	 * Create an IteratorIterator to flatten a nested iterator.
	 *
	 * @param \Traversable $iterator The input iterator.
	 * @return IteratorIterator The flattened IteratorIterator instance.
	 */
	public function Iterator(\Traversable $iterator): IteratorIterator
	{
		return new IteratorIterator($iterator);
	}

	/**
	 * Create a LimitIterator to limit the number of elements iterated.
	 *
	 * @param \Traversable $iterator The input iterator.
	 * @param int $offset The starting offset.
	 * @param int $count The number of elements to iterate.
	 * @return LimitIterator The LimitIterator instance.
	 */
	public function Limit(\Traversable $iterator, int $offset, int $count): LimitIterator
	{
		return new LimitIterator($iterator, $offset, $count);
	}

	/**
	 * Create a MultipleIterator for parallel iteration over multiple iterators.
	 *
	 * @param int $flags Optional flags (default: MIT_NEED_ALL | MIT_KEYS_NUMERIC).
	 * @return MultipleIterator The MultipleIterator instance.
	 */
	public function Multiple(int $flags = MultipleIterator::MIT_NEED_ALL | MultipleIterator::MIT_KEYS_NUMERIC): MultipleIterator
	{
		return new MultipleIterator($flags);
	}

	/**
	 * Attach an iterator to a MultipleIterator.
	 *
	 * @param MultipleIterator $multipleIterator The MultipleIterator instance.
	 * @param \Iterator $iterator The iterator to attach.
	 * @return void
	 */
	public function addIterator(MultipleIterator $multipleIterator, \Iterator $iterator): void
	{
		$multipleIterator->attachIterator($iterator);
	}

	/**
	 * Create a NoRewindIterator to prevent rewinding the input iterator.
	 *
	 * @param \Traversable $iterator The input iterator.
	 * @return NoRewindIterator The NoRewindIterator instance.
	 */
	public function NoRewind(\Traversable $iterator): NoRewindIterator
	{
		return new NoRewindIterator($iterator);
	}

	// Recursive Iterators

	/**
	 * Create a ParentIterator to filter only parent nodes.
	 *
	 * @param RecursiveIterator $iterator The recursive iterator.
	 * @return ParentIterator The ParentIterator instance.
	 */
	public function Parent(RecursiveIterator $iterator): ParentIterator
	{
		return new ParentIterator($iterator);
	}

	/**
	 * Create a RecursiveArrayIterator for traversing a multidimensional array.
	 *
	 * @param array $array The input array.
	 * @return RecursiveArrayIterator The RecursiveArrayIterator instance.
	 */
	public function RecursiveArray(array $array): RecursiveArrayIterator
	{
		return new RecursiveArrayIterator($array);
	}

	/**
	 * Create a RecursiveCallbackFilterIterator for filtering elements recursively with a callback.
	 *
	 * @param RecursiveIteratorIterator $iterator The recursive iterator.
	 * @param callable $callback The filtering callback.
	 * @return RecursiveCallbackFilterIterator The RecursiveCallbackFilterIterator instance.
	 */
	public function RecursiveCallbackFilter(RecursiveIteratorIterator $iterator, callable $callback): RecursiveCallbackFilterIterator
	{
		return new RecursiveCallbackFilterIterator($iterator, $callback);
	}

	/**
	 * Create a RecursiveCachingIterator to cache recursive elements.
	 *
	 * @param RecursiveIteratorIterator $iterator The recursive iterator.
	 * @param int $flags Optional caching flags (default: CATCH_GET_CHILD).
	 * @return RecursiveCachingIterator The RecursiveCachingIterator instance.
	 */
	public function RecursiveCaching(RecursiveIteratorIterator $iterator, int $flags = RecursiveCachingIterator::CATCH_GET_CHILD): RecursiveCachingIterator
	{
		return new RecursiveCachingIterator($iterator, $flags);
	}

	/**
	 * Create a RecursiveDirectoryIterator to recursively iterate over directories.
	 *
	 * @param string $path The directory path.
	 * @param int $flags Optional flags (default: KEY_AS_PATHNAME | CURRENT_AS_FILEINFO).
	 * @return RecursiveDirectoryIterator The RecursiveDirectoryIterator instance.
	 */
	public function RecursiveDirectory(string $path, int $flags = FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO): RecursiveDirectoryIterator
	{
		return new RecursiveDirectoryIterator($path, $flags);
	}

	/**
	 * Create a RecursiveIteratorIterator to traverse recursive iterators.
	 *
	 * @param RecursiveIterator $iterator The recursive iterator.
	 * @return RecursiveIteratorIterator The RecursiveIteratorIterator instance.
	 */
	public function RecursiveIterator(RecursiveIterator $iterator): RecursiveIteratorIterator
	{
		return new RecursiveIteratorIterator($iterator);
	}

	/**
	 * Create a RecursiveRegexIterator to filter elements recursively based on a regex pattern.
	 *
	 * @param RecursiveIteratorIterator $iterator The recursive iterator.
	 * @param string $pattern The regex pattern.
	 * @return RecursiveRegexIterator The RecursiveRegexIterator instance.
	 */
	public function RecursiveRegEx(RecursiveIteratorIterator $iterator, string $pattern): RecursiveRegexIterator
	{
		return new RecursiveRegexIterator($iterator, $pattern);
	}

	/**
	 * Create a RecursiveTreeIterator to traverse hierarchical structures.
	 *
	 * @param RecursiveIterator $iterator The recursive iterator.
	 * @return RecursiveTreeIterator The RecursiveTreeIterator instance.
	 */
	public function RecursiveTree(RecursiveIterator $iterator): RecursiveTreeIterator
	{
		return new RecursiveTreeIterator($iterator);
	}

	// RegexIterator

	/**
	 * Create a RegexIterator to filter elements based on a regular expression.
	 *
	 * @param \Iterator $iterator The input iterator.
	 * @param string $pattern The regex pattern.
	 * @param string $modeKey The mode key for how the regex should be applied (e.g., 'match', 'replace').
	 * @param int $flags Optional flags.
	 * @param int $pregFlags Optional preg_match flags.
	 * @return RegexIterator The RegexIterator instance.
	 */
	public function RegEx(\Iterator $iterator, string $pattern, string $modeKey = 'match', int $flags = 0, int $pregFlags = 0): RegexIterator
	{
		$mode = $this->regexModes['modes'][$modeKey] ?? RegexIterator::MATCH;
		return new RegexIterator($iterator, $pattern, $mode, $flags, $pregFlags);
	}

	// Utility Methods

	/**
	 * Apply a callback function to each element of an iterator.
	 *
	 * @param \Traversable $iterator The input iterator.
	 * @param callable $callback The callback function to apply.
	 * @return int The number of times the callback was applied.
	 */
	public function applyCallback(\Traversable $iterator, callable $callback): int
	{
		return iterator_apply($iterator, $callback);
	}

	/**
	 * Count the number of elements in an iterator.
	 *
	 * @param \Traversable $iterator The input iterator.
	 * @return int The number of elements in the iterator.
	 */
	public function count(\Traversable $iterator): int
	{
		return iterator_count($iterator);
	}

	/**
	 * Convert an iterator to an array.
	 *
	 * @param \Traversable $iterator The input iterator.
	 * @param bool $useKeys Whether to use the iterator keys in the array.
	 * @return array The array of elements from the iterator.
	 */
	public function toArray(\Traversable $iterator, bool $useKeys = true): array
	{
		return iterator_to_array($iterator, $useKeys);
	}
}
