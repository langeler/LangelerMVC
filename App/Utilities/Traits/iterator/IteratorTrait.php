<?php

namespace App\Utilities\Traits\Iterator;

use AppendIterator;
use ArrayIterator;
use CachingIterator;
use CallbackFilterIterator;
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
use RegexIterator;
use SeekableIterator;

/**
 * Methods for Standard Iterators.
 */
trait IteratorTrait
{
	/**
	 * Settings for various Standard Iterators.
	 *
	 * This property provides a centralized configuration for different iterator classes,
	 * including their class references, flags, modes, and additional options.
	 */
	private readonly array $iteratorSettings;

	public function __construct()
	{
		$this->iteratorSettings = [
			// Configuration for AppendIterator
			'AppendIterator' => [
				'class' => AppendIterator::class, // Appends multiple iterators into a single iterator
			],

			// Configuration for ArrayIterator
			'ArrayIterator' => [
				'class' => ArrayIterator::class, // Iterates over arrays and objects
				'flag' => [
					'asPropy' => ArrayIterator::ARRAY_AS_PROPS, // Treat array elements as object properties
					'stdProps' => ArrayIterator::STD_PROP_LIST, // Standard property list for objects
				],
			],

			// Configuration for CachingIterator
			'CachingIterator' => [
				'class' => CachingIterator::class, // Caches elements of the inner iterator
				'flag' => [
					'fullCache' => CachingIterator::FULL_CACHE, // Enables full caching of iterator results
					'callToString' => CachingIterator::CALL_TOSTRING, // Calls toString() on elements during iteration
					'catchGetChildren' => CachingIterator::CATCH_GET_CHILD, // Catches exceptions from getChildren()
				],
			],

			// Configuration for CallbackFilterIterator
			'CallbackFilterIterator' => [
				'class' => CallbackFilterIterator::class, // Filters elements using a callback function
			],

			// Configuration for DirectoryIterator
			'DirectoryIterator' => [
				'class' => DirectoryIterator::class, // Iterates over directory entries
			],

			// Configuration for EmptyIterator
			'EmptyIterator' => [
				'class' => EmptyIterator::class, // An empty iterator with no elements
			],

			// Configuration for FilesystemIterator
			'FilesystemIterator' => [
				'class' => FilesystemIterator::class, // Iterates over files in a directory
				'mode' => [
					'currentAsFileInfo' => FilesystemIterator::CURRENT_AS_FILEINFO, // Returns SplFileInfo objects
					'currentAsPathname' => FilesystemIterator::CURRENT_AS_PATHNAME, // Returns pathnames
					'currentAsSelf' => FilesystemIterator::CURRENT_AS_SELF, // Returns the current object instance
					'followSymlinks' => FilesystemIterator::FOLLOW_SYMLINKS, // Follows symbolic links
					'keyAsFilename' => FilesystemIterator::KEY_AS_FILENAME, // Uses filenames as keys
					'keyAsPathname' => FilesystemIterator::KEY_AS_PATHNAME, // Uses pathnames as keys
					'newCurrentKey' => FilesystemIterator::NEW_CURRENT_AND_KEY, // Assigns new keys and values
					'skipDots' => FilesystemIterator::SKIP_DOTS, // Skips '.' and '..' entries
					'unixPaths' => FilesystemIterator::UNIX_PATHS, // Uses UNIX-style path separators
				],
			],

			// Configuration for FilterIterator
			'FilterIterator' => [
				'class' => FilterIterator::class, // Filters elements of the inner iterator
			],

			// Configuration for GlobIterator
			'GlobIterator' => [
				'class' => GlobIterator::class, // Iterates over files matching a glob pattern
			],

			// Configuration for InfiniteIterator
			'InfiniteIterator' => [
				'class' => InfiniteIterator::class, // Repeats elements of the inner iterator indefinitely
			],

			// Configuration for IteratorIterator
			'IteratorIterator' => [
				'class' => IteratorIterator::class, // Wraps a regular iterator into a fully-featured iterator
			],

			// Configuration for LimitIterator
			'LimitIterator' => [
				'class' => LimitIterator::class, // Limits the number of elements from the inner iterator
			],

			// Configuration for MultipleIterator
			'MultipleIterator' => [
				'class' => MultipleIterator::class, // Iterates over multiple iterators in parallel
				'flag' => [
					'keysNumeric' => MultipleIterator::MIT_KEYS_NUMERIC, // Assigns numeric keys
					'needAll' => MultipleIterator::MIT_NEED_ALL, // Requires all iterators to have elements
					'needAny' => MultipleIterator::MIT_NEED_ANY, // Requires at least one iterator to have elements
				],
			],

			// Configuration for NoRewindIterator
			'NoRewindIterator' => [
				'class' => NoRewindIterator::class, // Prevents rewinding of the inner iterator
			],

			// Configuration for ParentIterator
			'ParentIterator' => [
				'class' => ParentIterator::class, // Iterates only over parent nodes in a recursive structure
			],

			// Configuration for RegexIterator
			'RegexIterator' => [
				'class' => RegexIterator::class, // Filters elements using a regular expression
				'mode' => [
					'match' => RegexIterator::MATCH, // Filters elements that match the regex
					'getMatches' => RegexIterator::GET_MATCH, // Returns regex matches as an array
					'allMatches' => RegexIterator::ALL_MATCHES, // Returns all matches for each element
					'captureGroups' => RegexIterator::USE_KEY, // Uses regex capture groups as keys
					'invertMatch' => RegexIterator::INVERT_MATCH, // Inverts the match condition
				],
			],

			// Configuration for SeekableIterator
			'SeekableIterator' => [
				'class' => SeekableIterator::class, // Allows seeking to a specific position in the iterator
			],
		];
	}

	/**
		 * Create an AppendIterator instance.
		 *
		 * @return AppendIterator
		 */
		public function AppendIterator(): AppendIterator
		{
			return new ($this->resolve('AppendIterator'))();
		}

		/**
		 * Create an ArrayIterator instance.
		 *
		 * @param array $data The array data to iterate over.
		 * @param array $settings Custom settings for ArrayIterator.
		 * @return ArrayIterator
		 */
		public function ArrayIterator(array $data, array $settings = []): ArrayIterator
		{
			return new ($this->resolve('ArrayIterator'))(
				$data,
				$this->fetchSettings('ArrayIterator', $settings)['flag']['asProps'] ?? $this->iteratorSettings['ArrayIterator']['flag']['asProps']
			);
		}

		/**
		 * Create a CachingIterator instance.
		 *
		 * @param \Iterator $iterator The inner iterator.
		 * @param array $settings Custom settings for CachingIterator.
		 * @return CachingIterator
		 */
		public function CachingIterator(\Iterator $iterator, array $settings = []): CachingIterator
		{
			return new ($this->resolve('CachingIterator'))(
				$iterator,
				$this->fetchSettings('CachingIterator', $settings)['flag']['fullCache'] ?? $this->iteratorSettings['CachingIterator']['flag']['fullCache']
			);
		}

		/**
		 * Create a CallbackFilterIterator instance.
		 *
		 * @param \Iterator $iterator The inner iterator.
		 * @param callable $callback The callback function for filtering.
		 * @return CallbackFilterIterator
		 */
		public function CallbackFilterIterator(\Iterator $iterator, callable $callback): CallbackFilterIterator
		{
			return new ($this->resolve('CallbackFilterIterator'))($iterator, $callback);
		}

		/**
		 * Create a DirectoryIterator instance.
		 *
		 * @param string $path The directory path.
		 * @return DirectoryIterator
		 */
		public function DirectoryIterator(string $path): DirectoryIterator
		{
			return new ($this->resolve('DirectoryIterator'))($path);
		}

		/**
		 * Create an EmptyIterator instance.
		 *
		 * @return EmptyIterator
		 */
		public function EmptyIterator(): EmptyIterator
		{
			return new ($this->resolve('EmptyIterator'))();
		}

		/**
		 * Create a FilesystemIterator instance.
		 *
		 * @param string $path The path to iterate over.
		 * @param array $settings Custom settings for FilesystemIterator.
		 * @return FilesystemIterator
		 */
		public function FilesystemIterator(string $path, array $settings = []): FilesystemIterator
		{
			return new ($this->resolve('FilesystemIterator'))(
				$path,
				$this->fetchSettings('FilesystemIterator', $settings)['mode']['skipDots'] ?? $this->iteratorSettings['FilesystemIterator']['mode']['skipDots']
			);
		}

		/**
		 * Create a FilterIterator instance.
		 *
		 * @param \Iterator $iterator The inner iterator.
		 * @return FilterIterator
		 */
		public function FilterIterator(\Iterator $iterator): FilterIterator
		{
			return new ($this->resolve('FilterIterator'))($iterator);
		}

		/**
		 * Create a GlobIterator instance.
		 *
		 * @param string $pattern The glob pattern.
		 * @param int $flags The iterator flags.
		 * @return GlobIterator
		 */
		public function GlobIterator(string $pattern, int $flags = 0): GlobIterator
		{
			return new ($this->resolve('GlobIterator'))($pattern, $flags);
		}

		/**
		 * Create an InfiniteIterator instance.
		 *
		 * @param \Iterator $iterator The inner iterator.
		 * @return InfiniteIterator
		 */
		public function InfiniteIterator(\Iterator $iterator): InfiniteIterator
		{
			return new ($this->resolve('InfiniteIterator'))($iterator);
		}

		/**
		 * Create an IteratorIterator instance.
		 *
		 * @param \Iterator $iterator The inner iterator.
		 * @return IteratorIterator
		 */
		public function IteratorIterator(\Iterator $iterator): IteratorIterator
		{
			return new ($this->resolve('IteratorIterator'))($iterator);
		}

		/**
		 * Create a LimitIterator instance.
		 *
		 * @param \Iterator $iterator The inner iterator.
		 * @param int $offset The starting offset.
		 * @param int $count The number of items to iterate (-1 for all).
		 * @return LimitIterator
		 */
		public function LimitIterator(\Iterator $iterator, int $offset = 0, int $count = -1): LimitIterator
		{
			return new ($this->resolve('LimitIterator'))($iterator, $offset, $count);
		}

		/**
		 * Create a MultipleIterator instance.
		 *
		 * @param array $settings Custom settings for MultipleIterator.
		 * @return MultipleIterator
		 */
		public function MultipleIterator(array $settings = []): MultipleIterator
		{
			return new ($this->resolve('MultipleIterator'))(
				$this->fetchSettings('MultipleIterator', $settings)['flag']['needAll'] ?? $this->iteratorSettings['MultipleIterator']['flag']['needAll']
			);
		}

		/**
		 * Create a NoRewindIterator instance.
		 *
		 * @param \Iterator $iterator The inner iterator.
		 * @return NoRewindIterator
		 */
		public function NoRewindIterator(\Iterator $iterator): NoRewindIterator
		{
			return new ($this->resolve('NoRewindIterator'))($iterator);
		}

		/**
		 * Create a ParentIterator instance.
		 *
		 * @param \Iterator $iterator The inner iterator.
		 * @return ParentIterator
		 */
		public function ParentIterator(\Iterator $iterator): ParentIterator
		{
			return new ($this->resolve('ParentIterator'))($iterator);
		}

		/**
		 * Create a RegexIterator instance.
		 *
		 * @param \Iterator $iterator The inner iterator.
		 * @param string $regex The regular expression to match.
		 * @param array $settings Custom settings for RegexIterator.
		 * @return RegexIterator
		 */
		public function RegexIterator(\Iterator $iterator, string $regex, array $settings = []): RegexIterator
		{
			return new ($this->resolve('RegexIterator'))(
				$iterator,
				$regex,
				$this->fetchSettings('RegexIterator', $settings)['mode']['match'] ?? $this->iteratorSettings['RegexIterator']['mode']['match']
			);
		}

		/**
		 * Create a SeekableIterator instance.
		 *
		 * @param \Iterator $iterator The inner iterator.
		 * @return SeekableIterator
		 */
		public function SeekableIterator(\Iterator $iterator): SeekableIterator
		{
			return new ($this->resolve('SeekableIterator'))($iterator);
		}
	}
}
