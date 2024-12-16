<?php

namespace App\Utilities\Traits\Iterator;

use RecursiveArrayIterator;
use RecursiveCachingIterator;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveFilterIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RecursiveTreeIterator;

/**
 * Methods for Recursive Iterators.
 */
trait RecursiveIteratorTrait
{
	/**
	 * Settings for various Recursive Iterators.
	 *
	 * This property provides a centralized configuration for different recursive iterator classes,
	 * including their class references, flags, modes, and other custom options.
	 */
	private readonly array $recursiveIteratorSettings;

	public function __construct()
	{
		$this->recursiveIteratorSettings = [
			// Configuration for RecursiveArrayIterator
			'RecursiveArrayIterator' => [
				'class' => RecursiveArrayIterator::class, // Reference to the RecursiveArrayIterator class
				'flag' => [
					'asPropy' => RecursiveArrayIterator::ARRAY_AS_PROPS, // Treats array elements as object properties
					'stdProps' => RecursiveArrayIterator::STD_PROP_LIST, // Standard property list for array access
				],
			],

			// Configuration for RecursiveCachingIterator
			'RecursiveCachingIterator' => [
				'class' => RecursiveCachingIterator::class, // Reference to the RecursiveCachingIterator class
				'flag' => [
					'fullCache' => RecursiveCachingIterator::FULL_CACHE, // Enables full caching of iterator results
					'callToString' => RecursiveCachingIterator::CALL_TOSTRING, // Calls toString() for elements during iteration
					'catchGetChildren' => RecursiveCachingIterator::CATCH_GET_CHILD, // Catches exceptions from getChildren()
				],
			],

			// Configuration for RecursiveCallbackFilterIterator
			'RecursiveCallbackFilterIterator' => [
				'class' => RecursiveCallbackFilterIterator::class, // Reference to the RecursiveCallbackFilterIterator class
			],

			// Configuration for RecursiveDirectoryIterator
			'RecursiveDirectoryIterator' => [
				'class' => RecursiveDirectoryIterator::class, // Reference to the RecursiveDirectoryIterator class
				'flag' => [
					'skipDots' => RecursiveDirectoryIterator::SKIP_DOTS, // Skips '.' and '..' entries
					'unixPaths' => RecursiveDirectoryIterator::UNIX_PATHS, // Uses UNIX-style path separators
				],
				'mode' => [
					'asFileInfo' => RecursiveDirectoryIterator::CURRENT_AS_FILEINFO, // Returns SplFileInfo objects
					'asPathname' => RecursiveDirectoryIterator::CURRENT_AS_PATHNAME, // Returns pathname strings
					'asSelf' => RecursiveDirectoryIterator::CURRENT_AS_SELF, // Returns the current object instance
				],
			],

			// Configuration for RecursiveFilterIterator
			'RecursiveFilterIterator' => [
				'class' => RecursiveFilterIterator::class, // Reference to the RecursiveFilterIterator class
			],

			// Configuration for RecursiveIteratorIterator
			'RecursiveIteratorIterator' => [
				'class' => RecursiveIteratorIterator::class, // Reference to the RecursiveIteratorIterator class
				'mode' => [
					'leavesOnly' => RecursiveIteratorIterator::LEAVES_ONLY, // Visits only the leaf nodes
					'selfFirst' => RecursiveIteratorIterator::SELF_FIRST, // Visits the current node before its children
					'childFirst' => RecursiveIteratorIterator::CHILD_FIRST, // Visits the children before the current node
				],
			],

			// Configuration for RecursiveRegexIterator
			'RecursiveRegexIterator' => [
				'class' => RecursiveRegexIterator::class, // Reference to the RecursiveRegexIterator class
				'mode' => [
					'match' => RecursiveRegexIterator::MATCH, // Filters items that match the regex
					'replace' => RecursiveRegexIterator::REPLACE, // Replaces items based on the regex
					'split' => RecursiveRegexIterator::SPLIT, // Splits items based on the regex
					'invertMatch' => RecursiveRegexIterator::INVERT_MATCH, // Inverts the regex match condition
				],
			],

			// Configuration for RecursiveTreeIterator
			'RecursiveTreeIterator' => [
				'class' => RecursiveTreeIterator::class, // Reference to the RecursiveTreeIterator class
				'flag' => [
					'bypassCurrent' => RecursiveTreeIterator::BYPASS_CURRENT, // Skips the current element
					'bypassKey' => RecursiveTreeIterator::BYPASS_KEY, // Skips the current element's key
				],
				'prefix' => [
					'left' => RecursiveTreeIterator::PREFIX_LEFT, // Prefix for left child
					'midHasNext' => RecursiveTreeIterator::PREFIX_MID_HAS_NEXT, // Prefix for middle element with next sibling
					'midLast' => RecursiveTreeIterator::PREFIX_MID_LAST, // Prefix for middle element with no siblings
					'endHasNext' => RecursiveTreeIterator::PREFIX_END_HAS_NEXT, // Prefix for end element with more siblings
					'endLast' => RecursiveTreeIterator::PREFIX_END_LAST, // Prefix for end element with no siblings
					'right' => RecursiveTreeIterator::PREFIX_RIGHT, // Prefix for right child
				],
				'mode' => [
					'leavesOnly' => RecursiveTreeIterator::LEAVES_ONLY, // Visits only the leaves
					'selfFirst' => RecursiveTreeIterator::SELF_FIRST, // Visits the current node before its children
					'childFirst' => RecursiveTreeIterator::CHILD_FIRST, // Visits the children before the current node
				],
				'cache' => [
					'catchGetChild' => RecursiveTreeIterator::CATCH_GET_CHILD, // Catches exceptions from getChildren()
				],
			],
		];
	}

	/**
	 * Create a RecursiveArrayIterator instance.
	 *
	 * @param array $data The array data to iterate over.
	 * @param array $settings Custom settings for RecursiveArrayIterator.
	 * @return RecursiveArrayIterator
	 */
	public function RecursiveArrayIterator(array $data, array $settings = []): RecursiveArrayIterator
	{
		return new ($this->resolve('RecursiveArrayIterator'))(
			$data,
			$this->fetchSettings('RecursiveArrayIterator', $settings)['flag'] ?? $this->recursiveIteratorSettings['RecursiveArrayIterator']['flag']['asProps']
		);
	}

	/**
	 * Create a RecursiveCachingIterator instance.
	 *
	 * @param \Iterator $iterator The inner iterator.
	 * @param array $settings Custom settings for RecursiveCachingIterator.
	 * @return RecursiveCachingIterator
	 */
	public function RecursiveCachingIterator($iterator, array $settings = []): RecursiveCachingIterator
	{
		return new ($this->resolve('RecursiveCachingIterator'))(
			$iterator,
			$this->fetchSettings('RecursiveCachingIterator', $settings)['flag'] ?? $this->recursiveIteratorSettings['RecursiveCachingIterator']['flag']['fullCache']
		);
	}

	/**
	 * Create a RecursiveCallbackFilterIterator instance.
	 *
	 * @param \Iterator $iterator The inner iterator.
	 * @param callable $callback The callback function for filtering.
	 * @return RecursiveCallbackFilterIterator
	 */
	public function RecursiveCallbackFilterIterator($iterator, callable $callback): RecursiveCallbackFilterIterator
	{
		return new ($this->resolve('RecursiveCallbackFilterIterator'))(
			$iterator,
			$callback
		);
	}

	/**
	 * Create a RecursiveDirectoryIterator instance.
	 *
	 * @param string $path The directory path.
	 * @param array $settings Custom settings for RecursiveDirectoryIterator.
	 * @return RecursiveDirectoryIterator
	 */
	public function RecursiveDirectoryIterator(string $path, array $settings = []): RecursiveDirectoryIterator
	{
		return new ($this->resolve('RecursiveDirectoryIterator'))(
			$path,
			($this->fetchSettings('RecursiveDirectoryIterator', $settings)['flag'] ?? $this->recursiveIteratorSettings['RecursiveDirectoryIterator']['flag']['skipDots'])
			| ($this->fetchSettings('RecursiveDirectoryIterator', $settings)['mode'] ?? $this->recursiveIteratorSettings['RecursiveDirectoryIterator']['mode']['asFileInfo'])
		);
	}

	/**
	 * Create a RecursiveFilterIterator instance.
	 *
	 * @param \Iterator $iterator The inner iterator.
	 * @return RecursiveFilterIterator
	 */
	public function RecursiveFilterIterator($iterator): RecursiveFilterIterator
	{
		return new ($this->resolve('RecursiveFilterIterator'))($iterator);
	}

	/**
	 * Create a RecursiveIteratorIterator instance.
	 *
	 * @param \Iterator $iterator The inner iterator.
	 * @param array $settings Custom settings for RecursiveIteratorIterator.
	 * @return RecursiveIteratorIterator
	 */
	public function RecursiveIteratorIterator($iterator, array $settings = []): RecursiveIteratorIterator
	{
		return new ($this->resolve('RecursiveIteratorIterator'))(
			$iterator,
			$this->fetchSettings('RecursiveIteratorIterator', $settings)['mode'] ?? $this->recursiveIteratorSettings['RecursiveIteratorIterator']['mode']['selfFirst']
		);
	}

	/**
	 * Create a RecursiveRegexIterator instance.
	 *
	 * @param \Iterator $iterator The inner iterator.
	 * @param string $regex The regular expression to match.
	 * @param array $settings Custom settings for RecursiveRegexIterator.
	 * @return RecursiveRegexIterator
	 */
	public function RecursiveRegexIterator($iterator, string $regex, array $settings = []): RecursiveRegexIterator
	{
		return new ($this->resolve('RecursiveRegexIterator'))(
			$iterator,
			$regex,
			$this->fetchSettings('RecursiveRegexIterator', $settings)['mode'] ?? $this->recursiveIteratorSettings['RecursiveRegexIterator']['mode']['match']
		);
	}

	/**
	 * Create a RecursiveTreeIterator instance.
	 *
	 * @param \Iterator $iterator The inner iterator.
	 * @param array $settings Custom settings for RecursiveTreeIterator.
	 * @return RecursiveTreeIterator
	 */
	public function RecursiveTreeIterator($iterator, array $settings = []): RecursiveTreeIterator
	{
		return new ($this->resolve('RecursiveTreeIterator'))(
			$iterator,
			($this->fetchSettings('RecursiveTreeIterator', $settings)['flag'] ?? $this->recursiveIteratorSettings['RecursiveTreeIterator']['flag']['bypassCurrent'])
			| ($this->fetchSettings('RecursiveTreeIterator', $settings)['prefix'] ?? $this->recursiveIteratorSettings['RecursiveTreeIterator']['prefix']['left'])
			| ($this->fetchSettings('RecursiveTreeIterator', $settings)['mode'] ?? $this->recursiveIteratorSettings['RecursiveTreeIterator']['mode']['selfFirst'])
			| ($this->fetchSettings('RecursiveTreeIterator', $settings)['cache'] ?? $this->recursiveIteratorSettings['RecursiveTreeIterator']['cache']['catchGetChild'])
		);
	}
}
