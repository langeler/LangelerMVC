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
	private readonly array $iteratorSettings;

	public function __construct()
	{
		$this->iteratorSettings = [
			'AppendIterator' => [
				'class' => AppendIterator::class,
			],
			'ArrayIterator' => [
				'class' => ArrayIterator::class,
				'flag' => [
					'asPropy' => ArrayIterator::ARRAY_AS_PROPS,
					'stdProps' => ArrayIterator::STD_PROP_LIST,
				],
			],
			'CachingIterator' => [
				'class' => CachingIterator::class,
				'flag' => [
					'fullCache' => CachingIterator::FULL_CACHE,
					'callToString' => CachingIterator::CALL_TOSTRING,
					'catchGetChildren' => CachingIterator::CATCH_GET_CHILD,
				],
			],
			'CallbackFilterIterator' => [
				'class' => CallbackFilterIterator::class,
			],
			'DirectoryIterator' => [
				'class' => DirectoryIterator::class,
			],
			'EmptyIterator' => [
				'class' => EmptyIterator::class,
			],
			'FilesystemIterator' => [
				'class' => FilesystemIterator::class,
				'mode' => [
					'currentAsFileInfo' => FilesystemIterator::CURRENT_AS_FILEINFO,
					'currentAsPathname' => FilesystemIterator::CURRENT_AS_PATHNAME,
					'currentAsSelf' => FilesystemIterator::CURRENT_AS_SELF,
					'followSymlinks' => FilesystemIterator::FOLLOW_SYMLINKS,
					'keyAsFilename' => FilesystemIterator::KEY_AS_FILENAME,
					'keyAsPathname' => FilesystemIterator::KEY_AS_PATHNAME,
					'newCurrentKey' => FilesystemIterator::NEW_CURRENT_AND_KEY,
					'skipDots' => FilesystemIterator::SKIP_DOTS,
					'unixPaths' => FilesystemIterator::UNIX_PATHS,
				],
			],
			'FilterIterator' => [
				'class' => FilterIterator::class,
			],
			'GlobIterator' => [
				'class' => GlobIterator::class,
			],
			'InfiniteIterator' => [
				'class' => InfiniteIterator::class,
			],
			'IteratorIterator' => [
				'class' => IteratorIterator::class,
			],
			'LimitIterator' => [
				'class' => LimitIterator::class,
			],
			'MultipleIterator' => [
				'class' => MultipleIterator::class,
				'flag' => [
					'keysNumeric' => MultipleIterator::MIT_KEYS_NUMERIC,
					'needAll' => MultipleIterator::MIT_NEED_ALL,
					'needAny' => MultipleIterator::MIT_NEED_ANY,
				],
			],
			'NoRewindIterator' => [
				'class' => NoRewindIterator::class,
			],
			'ParentIterator' => [
				'class' => ParentIterator::class,
			],
			'RegexIterator' => [
				'class' => RegexIterator::class,
				'mode' => [
					'match' => RegexIterator::MATCH,
					'getMatches' => RegexIterator::GET_MATCH,
					'allMatches' => RegexIterator::ALL_MATCHES,
					'captureGroups' => RegexIterator::USE_KEY,
					'invertMatch' => RegexIterator::INVERT_MATCH,
				],
			],
			'SeekableIterator' => [
				'class' => SeekableIterator::class,
			],
		];
	}

	public function AppendIterator(): AppendIterator
	{
		try {
			$iteratorClass = $this->resolve('AppendIterator');
			return new $iteratorClass();
		} catch (Throwable $e) {
			throw new IteratorException("Error creating AppendIterator: " . $e->getMessage(), 0, $e);
		}
	}

	public function ArrayIterator(array $data, array $settings = []): ArrayIterator
	{
		try {
			$iteratorClass = $this->resolve('ArrayIterator');
			$settings = $this->fetchSettings('ArrayIterator', $settings);
			$flags = $settings['flag'] ?? 0;
			return new $iteratorClass($data, $flags);
		} catch (Throwable $e) {
			throw new IteratorException("Error creating ArrayIterator: " . $e->getMessage(), 0, $e);
		}
	}

	public function CachingIterator($iterator, array $settings = []): CachingIterator
	{
		try {
			$iteratorClass = $this->resolve('CachingIterator');
			$settings = $this->fetchSettings('CachingIterator', $settings);
			$flags = $settings['flag'] ?? CachingIterator::FULL_CACHE;
			return new $iteratorClass($iterator, $flags);
		} catch (Throwable $e) {
			throw new IteratorException("Error creating CachingIterator: " . $e->getMessage(), 0, $e);
		}
	}

	public function CallbackFilterIterator($iterator, callable $callback): CallbackFilterIterator
	{
		try {
			// Return an anonymous class extending CallbackFilterIterator
			return new class($iterator, $callback) extends CallbackFilterIterator {
				public function __construct(Iterator $iterator, callable $callback)
				{
					parent::__construct($iterator, $callback);
				}

				// Optionally override methods if necessary
			};
		} catch (Throwable $e) {
			throw new IteratorException("Error creating CallbackFilterIterator: " . $e->getMessage(), 0, $e);
		}
	}

	public function DirectoryIterator(string $path): DirectoryIterator
	{
		try {
			$iteratorClass = $this->resolve('DirectoryIterator');
			return new $iteratorClass($path);
		} catch (Throwable $e) {
			throw new IteratorException("Error creating DirectoryIterator: " . $e->getMessage(), 0, $e);
		}
	}

	public function EmptyIterator(): EmptyIterator
	{
		try {
			$iteratorClass = $this->resolve('EmptyIterator');
			return new $iteratorClass();
		} catch (Throwable $e) {
			throw new IteratorException("Error creating EmptyIterator: " . $e->getMessage(), 0, $e);
		}
	}

	public function FilesystemIterator(string $path, array $settings = []): FilesystemIterator
	{
		try {
			$iteratorClass = $this->resolve('FilesystemIterator');
			$settings = $this->fetchSettings('FilesystemIterator', $settings);
			$flags = $settings['mode'] ?? FilesystemIterator::SKIP_DOTS;
			return new $iteratorClass($path, $flags);
		} catch (Throwable $e) {
			throw new IteratorException("Error creating FilesystemIterator: " . $e->getMessage(), 0, $e);
		}
	}

	public function FilterIterator($iterator): FilterIterator
	{
		try {
			// Return an anonymous class extending FilterIterator
			return new class($iterator) extends FilterIterator {
				public function __construct(Iterator $iterator)
				{
					parent::__construct($iterator);
				}

				// Optionally override methods if necessary
			};
		} catch (Throwable $e) {
			throw new IteratorException("Error creating FilterIterator: " . $e->getMessage(), 0, $e);
		}
	}

	public function GlobIterator(string $pattern, int $flags = 0): GlobIterator
	{
		try {
			$iteratorClass = $this->resolve('GlobIterator');
			return new $iteratorClass($pattern, $flags);
		} catch (Throwable $e) {
			throw new IteratorException("Error creating GlobIterator: " . $e->getMessage(), 0, $e);
		}
	}

	public function InfiniteIterator($iterator): InfiniteIterator
	{
		try {
			$iteratorClass = $this->resolve('InfiniteIterator');
			return new $iteratorClass($iterator);
		} catch (Throwable $e) {
			throw new IteratorException("Error creating InfiniteIterator: " . $e->getMessage(), 0, $e);
		}
	}

	public function IteratorIterator($iterator): IteratorIterator
	{
		try {
			$iteratorClass = $this->resolve('IteratorIterator');
			return new $iteratorClass($iterator);
		} catch (Throwable $e) {
			throw new IteratorException("Error creating IteratorIterator: " . $e->getMessage(), 0, $e);
		}
	}

	public function LimitIterator($iterator, int $offset = 0, int $count = -1): LimitIterator
	{
		try {
			$iteratorClass = $this->resolve('LimitIterator');
			return new $iteratorClass($iterator, $offset, $count);
		} catch (Throwable $e) {
			throw new IteratorException("Error creating LimitIterator: " . $e->getMessage(), 0, $e);
		}
	}

	public function NoRewindIterator($iterator): NoRewindIterator
	{
		try {
			$iteratorClass = $this->resolve('NoRewindIterator');
			return new $iteratorClass($iterator);
		} catch (Throwable $e) {
			throw new IteratorException("Error creating NoRewindIterator: " . $e->getMessage(), 0, $e);
		}
	}

	public function ParentIterator($iterator): ParentIterator
	{
		try {
			$iteratorClass = $this->resolve('ParentIterator');
			return new $iteratorClass($iterator);
		} catch (Throwable $e) {
			throw new IteratorException("Error creating ParentIterator: " . $e->getMessage(), 0, $e);
		}
	}

	public function RegexIterator($iterator, string $regex, array $settings = []): RegexIterator
	{
		try {
			$iteratorClass = $this->resolve('RegexIterator');
			$settings = $this->fetchSettings('RegexIterator', $settings);
			$mode = $settings['mode'] ?? RegexIterator::MATCH;
			return new $iteratorClass($iterator, $regex, $mode);
		} catch (Throwable $e) {
			throw new IteratorException("Error creating RegexIterator: " . $e->getMessage(), 0, $e);
		}
	}

	public function SeekableIterator($iterator): SeekableIterator
	{
		try {
			$iteratorClass = $this->resolve('SeekableIterator');
			return new $iteratorClass($iterator);
		} catch (Throwable $e) {
			throw new IteratorException("Error creating SeekableIterator: " . $e->getMessage(), 0, $e);
		}
	}
}
