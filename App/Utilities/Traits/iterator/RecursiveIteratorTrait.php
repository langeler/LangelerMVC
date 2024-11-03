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
	private readonly array $recursiveIteratorSettings;

	public function __construct()
	{
		$this->recursiveIteratorSettings = [
			'RecursiveArrayIterator' => [
				'class' => RecursiveArrayIterator::class,
				'flag' => [
					'asPropy' => RecursiveArrayIterator::ARRAY_AS_PROPS,
					'stdProps' => RecursiveArrayIterator::STD_PROP_LIST,
				],
			],
			'RecursiveCachingIterator' => [
				'class' => RecursiveCachingIterator::class,
				'flag' => [
					'fullCache' => RecursiveCachingIterator::FULL_CACHE,
					'callToString' => RecursiveCachingIterator::CALL_TOSTRING,
					'catchGetChildren' => RecursiveCachingIterator::CATCH_GET_CHILD,
				],
			],
			'RecursiveCallbackFilterIterator' => [
				'class' => RecursiveCallbackFilterIterator::class,
			],
			'RecursiveDirectoryIterator' => [
				'class' => RecursiveDirectoryIterator::class,
				'flag' => [
					'skipDots' => RecursiveDirectoryIterator::SKIP_DOTS,
					'unixPaths' => RecursiveDirectoryIterator::UNIX_PATHS,
				],
				'mode' => [
					'asFileInfo' => RecursiveDirectoryIterator::CURRENT_AS_FILEINFO,
					'asPathname' => RecursiveDirectoryIterator::CURRENT_AS_PATHNAME,
					'asSelf' => RecursiveDirectoryIterator::CURRENT_AS_SELF,
				],
			],
			'RecursiveFilterIterator' => [
				'class' => RecursiveFilterIterator::class,
			],
			'RecursiveIteratorIterator' => [
				'class' => RecursiveIteratorIterator::class,
				'mode' => [
					'leavesOnly' => RecursiveIteratorIterator::LEAVES_ONLY,
					'selfFirst' => RecursiveIteratorIterator::SELF_FIRST,
					'childFirst' => RecursiveIteratorIterator::CHILD_FIRST,
				],
			],
			'RecursiveRegexIterator' => [
				'class' => RecursiveRegexIterator::class,
				'mode' => [
					'match' => RecursiveRegexIterator::MATCH,
					'replace' => RecursiveRegexIterator::REPLACE,
					'split' => RecursiveRegexIterator::SPLIT,
					'invertMatch' => RecursiveRegexIterator::INVERT_MATCH,
				],
			],
			'RecursiveTreeIterator' => [
				'class' => RecursiveTreeIterator::class,
				'flag' => [
					'bypassCurrent' => RecursiveTreeIterator::BYPASS_CURRENT,   // Bypass current element
					'bypassKey' => RecursiveTreeIterator::BYPASS_KEY,           // Bypass current element's key
				],
				'prefix' => [
					'left' => RecursiveTreeIterator::PREFIX_LEFT,               // Prefix for left child
					'midHasNext' => RecursiveTreeIterator::PREFIX_MID_HAS_NEXT, // Prefix for middle element with next sibling
					'midLast' => RecursiveTreeIterator::PREFIX_MID_LAST,        // Prefix for middle element with no more siblings
					'endHasNext' => RecursiveTreeIterator::PREFIX_END_HAS_NEXT, // Prefix for end element with more siblings
					'endLast' => RecursiveTreeIterator::PREFIX_END_LAST,        // Prefix for end element with no more siblings
					'right' => RecursiveTreeIterator::PREFIX_RIGHT,             // Prefix for right child
				],
				'mode' => [
					'leavesOnly' => RecursiveTreeIterator::LEAVES_ONLY,         // Only iterate over leaves
					'selfFirst' => RecursiveTreeIterator::SELF_FIRST,           // Visit current node before children
					'childFirst' => RecursiveTreeIterator::CHILD_FIRST,         // Visit children before current node
				],
				'cache' => [
					'catchGetChild' => RecursiveTreeIterator::CATCH_GET_CHILD,  // Catch exceptions from getChildren() method
				]
			],
		];
	}

public function RecursiveArrayIterator(array $data, array $settings = []): RecursiveArrayIterator
	{
		try {
			$iteratorClass = $this->resolve('RecursiveArrayIterator');
			$settings = $this->fetchSettings('RecursiveArrayIterator', $settings);
			$flags = $settings['flag'] ?? 0;
			return new $iteratorClass($data, $flags);
		} catch (Throwable $e) {
			throw new IteratorException("Error creating RecursiveArrayIterator: " . $e->getMessage(), 0, $e);
		}
	}

	public function RecursiveCachingIterator($iterator, array $settings = []): RecursiveCachingIterator
	{
		try {
			$iteratorClass = $this->resolve('RecursiveCachingIterator');
			$settings = $this->fetchSettings('RecursiveCachingIterator', $settings);
			$flags = $settings['flag'] ?? RecursiveCachingIterator::FULL_CACHE;
			return new $iteratorClass($iterator, $flags);
		} catch (Throwable $e) {
			throw new IteratorException("Error creating RecursiveCachingIterator: " . $e->getMessage(), 0, $e);
		}
	}

	public function RecursiveCallbackFilterIterator($iterator, callable $callback): RecursiveCallbackFilterIterator
	{
		try {
			$iteratorClass = $this->resolve('RecursiveCallbackFilterIterator');
			return new $iteratorClass($iterator, $callback);
		} catch (Throwable $e) {
			throw new IteratorException("Error creating RecursiveCallbackFilterIterator: " . $e->getMessage(), 0, $e);
		}
	}

	public function RecursiveDirectoryIterator(string $path, array $settings = []): RecursiveDirectoryIterator
	{
		try {
			$iteratorClass = $this->resolve('RecursiveDirectoryIterator');
			$settings = $this->fetchSettings('RecursiveDirectoryIterator', $settings);
			$flags = $settings['flag'] ?? RecursiveDirectoryIterator::SKIP_DOTS;
			$mode = $settings['mode'] ?? RecursiveDirectoryIterator::CURRENT_AS_FILEINFO;
			return new $iteratorClass($path, $flags | $mode);
		} catch (Throwable $e) {
			throw new IteratorException("Error creating RecursiveDirectoryIterator: " . $e->getMessage(), 0, $e);
		}
	}

	public function RecursiveFilterIterator($iterator, callable $callback): RecursiveFilterIterator
	{
		try {
			$iteratorClass = $this->resolve('RecursiveFilterIterator');
			return new $iteratorClass($iterator, $callback);
		} catch (Throwable $e) {
			throw new IteratorException("Error creating RecursiveFilterIterator: " . $e->getMessage(), 0, $e);
		}
	}

	public function RecursiveIteratorIterator($iterator, array $settings = []): RecursiveIteratorIterator
	{
		try {
			$iteratorClass = $this->resolve('RecursiveIteratorIterator');
			$settings = $this->fetchSettings('RecursiveIteratorIterator', $settings);
			$mode = $settings['mode'] ?? RecursiveIteratorIterator::SELF_FIRST;
			return new $iteratorClass($iterator, $mode);
		} catch (Throwable $e) {
			throw new IteratorException("Error creating RecursiveIteratorIterator: " . $e->getMessage(), 0, $e);
		}
	}

	public function RecursiveRegexIterator($iterator, string $regex, array $settings = []): RecursiveRegexIterator
	{
		try {
			$iteratorClass = $this->resolve('RecursiveRegexIterator');
			$settings = $this->fetchSettings('RecursiveRegexIterator', $settings);
			$mode = $settings['mode'] ?? RecursiveRegexIterator::MATCH;
			return new $iteratorClass($iterator, $regex, $mode);
		} catch (Throwable $e) {
			throw new IteratorException("Error creating RecursiveRegexIterator: " . $e->getMessage(), 0, $e);
		}
	}

	public function RecursiveTreeIterator($iterator, array $settings = []): RecursiveTreeIterator
	{
		try {
			$iteratorClass = $this->resolve('RecursiveTreeIterator');
			$settings = $this->fetchSettings('RecursiveTreeIterator', $settings);
			$flags = $settings['flag'] ?? RecursiveTreeIterator::BYPASS_CURRENT;
			$prefix = $settings['prefix'] ?? RecursiveTreeIterator::PREFIX_LEFT;
			$mode = $settings['mode'] ?? RecursiveTreeIterator::SELF_FIRST;
			$cache = $settings['cache'] ?? RecursiveTreeIterator::CATCH_GET_CHILD;
			return new $iteratorClass($iterator, $flags | $prefix | $mode | $cache);
		} catch (Throwable $e) {
			throw new IteratorException("Error creating RecursiveTreeIterator: " . $e->getMessage(), 0, $e);
		}
	}
}
