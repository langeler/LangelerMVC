IteratorManager Class Documentation

Overview:
The IteratorManager class is designed to manage and instantiate various types of iterators available in PHP, particularly focusing on standard, recursive, and SPL-based iterators. This class allows you to work with iterators while providing a flexible configuration system via the $settings property. Additionally, the class offers utility methods for iterator operations such as applying callbacks, counting elements, and converting iterators to arrays.

Core Properties

	•	$iterator:
Type: ?Iterator
This property holds the current iterator instance, which can be any standard or recursive iterator managed by the class.
	•	$settings:
Type: array
Contains default settings and configurations for each iterator type, which can be customized when creating instances. This array is divided into keys for each iterator type, allowing fine-tuned control over behavior such as flags, paths, modes, and other options.

Supported Iterators

The IteratorManager class supports the following iterators:

1. Standard Iterators

	•	AppendIterator
	•	ArrayIterator
	•	CachingIterator
	•	CallbackFilterIterator
	•	DirectoryIterator
	•	EmptyIterator
	•	FilesystemIterator
	•	FilterIterator
	•	GlobIterator
	•	InfiniteIterator
	•	Iterator
	•	IteratorIterator
	•	LimitIterator
	•	MultipleIterator
	•	NoRewindIterator
	•	ParentIterator
	•	RegexIterator
	•	TreeIterator

2. Recursive Iterators

	•	RecursiveArrayIterator
	•	RecursiveCachingIterator
	•	RecursiveCallbackFilterIterator
	•	RecursiveDirectoryIterator
	•	RecursiveFilterIterator
	•	RecursiveIterator
	•	RecursiveIteratorIterator
	•	RecursiveRegexIterator
	•	RecursiveTreeIterator

3. SPLFileInfo-based Iterators

	•	SplFileInfo

Key Methods

Core Iterator Methods:

	•	getIterator(): Returns the current iterator instance.
	•	setIterator(Iterator $iterator): Sets a new iterator instance.
	•	current(): Returns the current element in the iterator.
	•	key(): Returns the key of the current element.
	•	next(): Advances the iterator to the next element.
	•	rewind(): Rewinds the iterator to the first element.
	•	valid(): Checks if the current iterator position is valid.
	•	getDepth(): Retrieves the current depth for RecursiveIteratorIterator.
	•	hasChildren(): Checks if the current element has children for recursive iterators.
	•	getChildren(): Retrieves the children iterator for recursive iterators.

Utility Methods:

	•	Apply(Iterator $iterator, callable $callback): Applies a callback function to the elements of an iterator.
	•	count(Iterator $iterator): Counts the number of elements in the iterator.
	•	toArray(Iterator $iterator, bool $useKeys): Converts an iterator to an array.

Directory-Specific Methods:

	•	getPermissions(): Retrieves the file permissions of the current element in a RecursiveDirectoryIterator.
	•	getSize(): Gets the file size of the current element.
	•	RealPath(): Returns the real path of the current file or directory.
	•	isFile(): Checks if the current element is a file.
	•	isDirectory(): Checks if the current element is a directory.
	•	FileInfo(string $filePath): Returns a SplFileInfo object for a given file path.

Settings Overview

The $settings array allows customization of each iterator type. Below is an overview of the key-value structure available for each iterator:

Example of Defining $settings:

$settings = [
	'ArrayIterator' => [
		'flag' => [
			'asPropy' => ArrayIterator::ARRAY_AS_PROPS,
			'stdProps' => ArrayIterator::STD_PROP_LIST,
		],
		'data' => [1, 2, 3],  // Default data for the iterator
	],
	'RecursiveCachingIterator' => [
		'flag' => [
			'fullCache' => CachingIterator::FULL_CACHE,
		],
	],
];

Available Settings Keys:

Here is a summarized list of the available settings keys and values for the various iterators in the IteratorManager class:

Standard Iterators

	1.	AppendIterator
	•	No additional settings.
	2.	ArrayIterator
	•	flag:
	•	ArrayIterator::ARRAY_AS_PROPS
	•	ArrayIterator::STD_PROP_LIST
	•	data: Default is an empty array.
	3.	CachingIterator
	•	flag:
	•	CachingIterator::FULL_CACHE
	•	CachingIterator::CALL_TOSTRING
	•	CachingIterator::CATCH_GET_CHILD
	4.	CallbackFilterIterator
	•	No additional settings.
	5.	DirectoryIterator
	•	path: Default is . (current directory).
	•	flag:
	•	DirectoryIterator::SKIP_DOTS
	•	DirectoryIterator::UNIX_PATHS
	6.	EmptyIterator
	•	No additional settings.
	7.	FilesystemIterator
	•	path: Default is . (current directory).
	•	mode:
	•	FilesystemIterator::CURRENT_AS_PATHNAME
	•	FilesystemIterator::KEY_AS_PATHNAME
	•	FilesystemIterator::NEW_CURRENT_AND_KEY
	•	FilesystemIterator::FOLLOW_SYMLINKS
	•	FilesystemIterator::SKIP_DOTS
	•	FilesystemIterator::UNIX_PATHS
	8.	FilterIterator
	•	No additional settings.
	9.	GlobIterator
	•	pattern: Default is * (all files).
	10.	IteratorIterator
	•	No additional settings.
	11.	InfiniteIterator
	•	No additional settings.
	12.	LimitIterator
	•	offset: Default is 0.
	•	count: Default is -1 (unlimited).
	13.	MultipleIterator
	•	flag:
	•	MultipleIterator::MIT_KEYS_NUMERIC
	•	MultipleIterator::MIT_NEED_ALL
	14.	NoRewindIterator
	•	No additional settings.
	15.	ParentIterator
	•	No additional settings.
	16.	RegexIterator
	•	regex: Default is an empty string.
	•	mode:
	•	RegexIterator::MATCH
	•	RegexIterator::USE_NORMAL
	•	RegexIterator::REPLACE
	•	RegexIterator::SPLIT
	•	RegexIterator::PREGGREP_INVERT

Recursive Iterators

	1.	RecursiveArrayIterator
	•	flag:
	•	RecursiveArrayIterator::ARRAY_AS_PROPS
	•	RecursiveArrayIterator::STD_PROP_LIST
	•	data: Default is an empty array.
	2.	RecursiveCachingIterator
	•	flag:
	•	RecursiveCachingIterator::FULL_CACHE
	•	RecursiveCachingIterator::CALL_TOSTRING
	•	RecursiveCachingIterator::CATCH_GET_CHILD
	3.	RecursiveCallbackFilterIterator
	•	No additional settings.
	4.	RecursiveDirectoryIterator
	•	path: Default is . (current directory).
	•	mode:
	•	RecursiveDirectoryIterator::CURRENT_AS_PATHNAME
	•	RecursiveDirectoryIterator::KEY_AS_PATHNAME
	•	RecursiveDirectoryIterator::FOLLOW_SYMLINKS
	•	RecursiveDirectoryIterator::SKIP_DOTS
	•	RecursiveDirectoryIterator::UNIX_PATHS
	5.	RecursiveFilterIterator
	•	No additional settings.
	6.	RecursiveIteratorIterator
	•	mode:
	•	RecursiveIteratorIterator::LEAVES_ONLY
	•	RecursiveIteratorIterator::SELF_FIRST
	•	RecursiveIteratorIterator::CHILD_FIRST
	•	RecursiveIteratorIterator::CATCH_GET_CHILD
	7.	RecursiveRegexIterator
	•	regex: Default is an empty string.
	•	mode:
	•	RegexIterator::MATCH
	•	RegexIterator::USE_NORMAL
	•	RegexIterator::REPLACE
	•	RegexIterator::SPLIT
	8.	RecursiveTreeIterator
	•	flag:
	•	RecursiveTreeIterator::BYPASS_CURRENT
	•	RecursiveTreeIterator::CATCH_GET_CHILD
	•	RecursiveTreeIterator::CHILD_FIRST
	•	RecursiveTreeIterator::PREFIX_LEFT
	•	RecursiveTreeIterator::PREFIX_LEAF
	•	RecursiveTreeIterator::PREFIX_MID_HAS_NEXT
	•	RecursiveTreeIterator::PREFIX_NO_NEXT
	•	RecursiveTreeIterator::PREFIX_RIGHT

SPLFileInfo-based Iterators

	1.	SplFileInfo
	•	No additional settings.

TreeIterator

	•	flag:
	•	TreeIterator::BYPASS_CURRENT
	•	TreeIterator::CATCH_GET_CHILD
	•	TreeIterator::CHILD_FIRST
	•	TreeIterator::PREFIX_LEFT
	•	TreeIterator::PREFIX_LEAF
	•	TreeIterator::PREFIX_MID_HAS_NEXT
	•	TreeIterator::PREFIX_NO_NEXT
	•	TreeIterator::PREFIX_RIGHT

This list provides a complete overview of all the available settings keys and values for each iterator type. Each setting allows for specific customization of iterator behavior, ensuring flexibility and control over how data is iterated.

This overview highlights how to use the IteratorManager class to work with various iterators, control behavior through $settings, and leverage the provided utility methods for iterator manipulation.

Full Coverage of Iterator Methods

1. AppendIterator

public function Append(array $iterators = [], array $settings = []): AppendIterator

	•	Purpose: Combines multiple iterators into a single iterator.
	•	Settings: No additional settings.
	•	Usage: Pass an array of iterators to append them into one iterator.

2. ArrayIterator

public function Array(array $data = [], array $settings = []): ArrayIterator

	•	Purpose: Iterates over an array-like structure.
	•	Settings:
	•	flag: ArrayIterator::ARRAY_AS_PROPS, ArrayIterator::STD_PROP_LIST
	•	data: Default data to iterate over.
	•	Usage: Can iterate over an array, with the option to configure how the array’s properties are handled.

3. CachingIterator

public function Caching(?RecursiveIterator $iterator, array $settings = []): CachingIterator

	•	Purpose: Caches the elements of the iterator for multiple passes.
	•	Settings:
	•	flag: CachingIterator::FULL_CACHE, CachingIterator::CALL_TOSTRING, CachingIterator::CATCH_GET_CHILD
	•	Usage: Enhances performance when the iterator needs to be traversed multiple times.

4. CallbackFilterIterator

public function CallbackFilter(?RecursiveIterator $iterator, callable $callback, array $settings = []): CallbackFilterIterator

	•	Purpose: Filters elements using a custom callback.
	•	Settings: No additional settings.
	•	Usage: Pass a callback function that determines whether an element is accepted.

5. DirectoryIterator

public function Directory(string $path = '.', array $settings = []): DirectoryIterator

	•	Purpose: Iterates over a directory’s contents.
	•	Settings:
	•	path: Default path to the directory.
	•	flag: DirectoryIterator::SKIP_DOTS, DirectoryIterator::UNIX_PATHS
	•	Usage: Useful for listing files and directories within a specified path.

6. EmptyIterator

public function Empty(array $settings = []): EmptyIterator

	•	Purpose: An iterator that contains no elements.
	•	Settings: No additional settings.
	•	Usage: Placeholder when no iteration is needed.

7. FilesystemIterator

public function Filesystem(string $path = '.', array $settings = []): FilesystemIterator

	•	Purpose: Iterates over a directory’s contents with more detailed control over file properties.
	•	Settings:
	•	path: Default path to directory.
	•	mode: FilesystemIterator::CURRENT_AS_PATHNAME, FilesystemIterator::SKIP_DOTS, FilesystemIterator::UNIX_PATHS
	•	Usage: Provides file-level control when iterating directories.

8. FilterIterator

public function Filter(?RecursiveIterator $iterator, callable $callback, array $settings = []): FilterIterator

	•	Purpose: Filters elements based on a callback function.
	•	Settings: No additional settings.
	•	Usage: Use this iterator to filter out unwanted elements based on a user-defined callback.

9. GlobIterator

public function Glob(string $pattern, array $settings = []): GlobIterator

	•	Purpose: Finds files using a pattern and iterates over them.
	•	Settings:
	•	pattern: The glob pattern to search for files.
	•	Usage: Efficient for matching file names based on patterns, like *.txt.

10. InfiniteIterator

public function Infinite(?RecursiveIterator $iterator, array $settings = []): InfiniteIterator

	•	Purpose: Iterates indefinitely over the provided iterator.
	•	Settings: No additional settings.
	•	Usage: Use for looping through an iterator forever.

11. IteratorIterator

public function Iterator(?RecursiveIterator $iterator, array $settings = []): IteratorIterator

	•	Purpose: Turns anything that is traversable into an iterator.
	•	Settings: No additional settings.
	•	Usage: For converting different traversable objects into iterators.

12. LimitIterator

public function Limit(?RecursiveIterator $iterator, array $settings = []): LimitIterator

	•	Purpose: Limits the number of elements to iterate.
	•	Settings:
	•	offset: Start at a specific position in the iterator.
	•	count: Number of elements to iterate.
	•	Usage: Useful for paginating through results.

13. MultipleIterator

public function Multiple(array $iterators = [], array $settings = []): MultipleIterator

	•	Purpose: Iterates over multiple iterators at once.
	•	Settings:
	•	flag: MultipleIterator::MIT_KEYS_NUMERIC, MultipleIterator::MIT_NEED_ALL
	•	Usage: Useful when merging several iterators into one.

14. NoRewindIterator

public function NoRewind(?RecursiveIterator $iterator, array $settings = []): NoRewindIterator

	•	Purpose: Prevents the iterator from rewinding.
	•	Settings: No additional settings.
	•	Usage: Ensures the iterator does not rewind after traversal.

15. ParentIterator

public function Parent(?RecursiveIterator $iterator, array $settings = []): ParentIterator

	•	Purpose: Iterates over the parent directory of a recursive iterator.
	•	Settings: No additional settings.
	•	Usage: Use to iterate only over parent directories.

16. RegexIterator

public function Regex(?RecursiveIterator $iterator, string $regex, array $settings = []): RegexIterator

	•	Purpose: Filters elements based on a regex pattern.
	•	Settings:
	•	regex: Regular expression pattern.
	•	mode: RegexIterator::MATCH, RegexIterator::REPLACE, etc.
	•	Usage: Filters iterator elements that match a regex.

17. RecursiveArrayIterator

public function RecursiveArray(array $data = [], array $settings = []): RecursiveArrayIterator

	•	Purpose: Iterates recursively over an array.
	•	Settings:
	•	flag: RecursiveArrayIterator::ARRAY_AS_PROPS, RecursiveArrayIterator::STD_PROP_LIST
	•	data: Data array.
	•	Usage: For recursive array structures.

18. RecursiveCachingIterator

public function RecursiveCaching(?RecursiveIterator $iterator, array $settings = []): RecursiveCachingIterator

	•	Purpose: Caches recursive iterator elements.
	•	Settings: Same as CachingIterator.
	•	Usage: Caches recursive iterator results.

19. RecursiveCallbackFilterIterator

public function RecursiveCallbackFilter(?RecursiveIterator $iterator, callable $callback, array $settings = []): RecursiveCallbackFilterIterator

	•	Purpose: Recursively filters elements using a callback.
	•	Settings: No additional settings.
	•	Usage: For recursive callback filtering.

20. RecursiveDirectoryIterator

public function RecursiveDirectory(string $path = '.', array $settings = []): RecursiveDirectoryIterator

	•	Purpose: Recursively iterates over a directory’s contents.
	•	Settings:
	•	path: Directory path.
	•	mode: RecursiveDirectoryIterator::CURRENT_AS_PATHNAME, etc.
	•	Usage: Use for traversing directories recursively.

21. RecursiveFilterIterator

public function RecursiveFilter(?RecursiveIterator $iterator, callable $callback, array $settings = []): RecursiveFilterIterator

	•	Purpose: Recursively filters elements using a callback.
	•	Settings: No additional settings.
	•	Usage: For recursive element filtering with user-defined logic.

22. RecursiveIteratorIterator

public function RecursiveIterator(?RecursiveIterator $iterator, array $settings = []): RecursiveIteratorIterator

	•	Purpose: Iterates over recursive iterators.
	•	Settings:
	•	mode: RecursiveIteratorIterator::LEAVES_ONLY, etc.
	•	Usage: Controls how the recursive iterator traverses.

23. RecursiveRegexIterator

public function RecursiveRegex(?RecursiveIterator $iterator, string $regex, array $settings = []): RecursiveRegexIterator

	•	Purpose: Filters recursive iterator elements based on a regex pattern.
	•	Settings: Same as RegexIterator.
	•	Usage: Recursively filters elements using regex.

24. RecursiveTreeIterator

public function RecursiveTree(?RecursiveIterator $iterator, array $settings = []): RecursiveTreeIterator

	•	Purpose: Visualizes tree structures in iterators.
	•	Settings:
	•	flag: Various flags for tree traversal.
	•	Usage: Use for iterating through tree-like structures.

25. TreeIterator

public function Tree(?RecursiveIterator $iterator, array $settings = []): TreeIterator

	•	Purpose: Similar to RecursiveTreeIterator, but for non-recursive iterators.
	•	Settings: Same as RecursiveTreeIterator.
	•	Usage: Traverse and display tree-like data structures.

Complete Settings Configuration

To define $settings for each method, you pass an array with iterator-specific keys. These keys control behavior such as flags, patterns, and paths. Here’s a configuration example:

$settings = [
	'ArrayIterator' => [
		'flag' => [
			'asPropy' => ArrayIterator::ARRAY_AS_PROPS,
			'stdProps' => ArrayIterator::STD_PROP_LIST,
		],
		'data' => [1, 2, 3],
	],
	'CachingIterator' => [
		'flag' => [
			'fullCache' => CachingIterator::FULL_CACHE,
		],
	],
	'RegexIterator' => [
		'regex' => '/^test/',
		'mode' => RegexIterator::MATCH,
	],
];

<?php

namespace App\Utilities;

use App\Utilities\Managers\IteratorManager;

class IteratorExamples
{
	private IteratorManager $iteratorManager;

	public function __construct()
	{
		$this->iteratorManager = new IteratorManager();
	}

	// Example 1: Using AppendIterator and toArray utility method
	public function runAppendExample(): void
	{
		// Appending multiple iterators
		echo implode(', ', $this->iteratorManager->toArray(
			$this->iteratorManager->Append(
				[
					$this->iteratorManager->Array([1, 2, 3], []), // Using the IteratorManager to create ArrayIterator
					$this->iteratorManager->Array([4, 5, 6], [])
				],
				[] // No additional settings for AppendIterator
			)
		)) . PHP_EOL;
	}

	// Example 2: Using RecursiveDirectoryIterator and count utility method
	public function runRecursiveDirectoryExample(): void
	{
		// Counting the number of files in a directory
		echo 'Total files: ' . $this->iteratorManager->count(
			$this->iteratorManager->RecursiveDirectory(
				'/path/to/directory', // Path to directory
				[
					'mode' => [
						'currentModeMask' => \RecursiveDirectoryIterator::SKIP_DOTS // Skipping dot files
					]
				]
			)
		) . PHP_EOL;
	}

	// Example 3: Using RegexIterator to filter files
	public function runRegexIteratorExample(): void
	{
		// Filtering files with .txt extension
		echo implode(PHP_EOL, $this->iteratorManager->toArray(
			$this->iteratorManager->Regex(
				$this->iteratorManager->RecursiveDirectory(
					'/path/to/files',
					[
						'mode' => [
							'currentModeMask' => \RecursiveDirectoryIterator::SKIP_DOTS
						]
					]
				),
				'/\.txt$/', // Regex pattern to match .txt files
				[] // No additional settings for RegexIterator
			)
		)) . PHP_EOL;
	}

	// Example 4: Using RecursiveCallbackFilterIterator and toArray method
	public function runRecursiveCallbackFilterExample(): void
	{
		// Filtering files larger than 1KB
		echo implode(PHP_EOL, $this->iteratorManager->toArray(
			$this->iteratorManager->RecursiveCallbackFilter(
				$this->iteratorManager->RecursiveDirectory(
					'/path/to/files',
					[
						'mode' => [
							'currentModeMask' => \RecursiveDirectoryIterator::SKIP_DOTS
						]
					]
				),
				function ($current) {
					return $current->isFile() && $current->getSize() > 1024; // Files larger than 1KB
				},
				[] // No additional settings for RecursiveCallbackFilterIterator
			)
		)) . PHP_EOL;
	}
}

<?php

namespace App\Utilities;

use App\Utilities\Managers\IteratorManager;

class IteratorPropertyExamples
{
	private IteratorManager $iteratorManager;

	public function __construct()
	{
		$this->iteratorManager = new IteratorManager();
	}

	// Example 1: Setting and getting the current iterator using the iterator property
	public function useIteratorPropertyDirectly(): void
	{
		// Creating an ArrayIterator and setting it as the current iterator
		$this->iteratorManager->setIterator(
			$this->iteratorManager->Array([10, 20, 30], []) // ArrayIterator
		);

		// Accessing the iterator using the getIterator method
		$iterator = $this->iteratorManager->getIterator();

		// Outputting values from the current iterator (ArrayIterator)
		foreach ($iterator as $value) {
			echo $value . PHP_EOL;
		}
	}

	// Example 2: Using utility methods with the iterator property set
	public function useIteratorWithUtilityMethods(): void
	{
		// Creating a RecursiveDirectoryIterator and setting it as the current iterator
		$this->iteratorManager->setIterator(
			$this->iteratorManager->RecursiveDirectory(
				'/path/to/files',
				[
					'mode' => [
						'currentModeMask' => \RecursiveDirectoryIterator::SKIP_DOTS
					]
				]
			)
		);

		// Using the count utility method on the currently set iterator
		$totalFiles = $this->iteratorManager->count($this->iteratorManager->getIterator());

		echo 'Total files in directory: ' . $totalFiles . PHP_EOL;
	}

	// Example 3: Modifying and reusing the current iterator
	public function modifyAndReuseIterator(): void
	{
		// Creating an AppendIterator with two ArrayIterators
		$this->iteratorManager->setIterator(
			$this->iteratorManager->Append(
				[
					$this->iteratorManager->Array([1, 2, 3], []),
					$this->iteratorManager->Array([4, 5, 6], [])
				],
				[] // No additional settings
			)
		);

		// Reusing the current iterator to print all elements
		foreach ($this->iteratorManager->getIterator() as $value) {
			echo $value . PHP_EOL;
		}

		// Modifying the iterator by adding another ArrayIterator
		$appendIterator = $this->iteratorManager->getIterator();
		$appendIterator->append(
			$this->iteratorManager->Array([7, 8, 9], [])
		);

		// Printing the modified iterator (now containing 3 arrays)
		foreach ($this->iteratorManager->getIterator() as $value) {
			echo $value . PHP_EOL;
		}
	}

	// Example 4: Filtering the current iterator using RegexIterator and utility methods
	public function filterCurrentIterator(): void
	{
		// Set a RecursiveDirectoryIterator as the current iterator
		$this->iteratorManager->setIterator(
			$this->iteratorManager->RecursiveDirectory(
				'/path/to/files',
				[
					'mode' => [
						'currentModeMask' => \RecursiveDirectoryIterator::SKIP_DOTS
					]
				]
			)
		);

		// Apply a Regex filter to the current iterator for .txt files
		$this->iteratorManager->setIterator(
			$this->iteratorManager->Regex(
				$this->iteratorManager->getIterator(), // Using the current iterator
				'/\.txt$/',
				[] // No additional settings
			)
		);

		// Output the filtered .txt files using the utility method
		echo implode(PHP_EOL, $this->iteratorManager->toArray($this->iteratorManager->getIterator())) . PHP_EOL;
	}
}

$iteratorManager->RecursiveDirectory('/path/to/dir', [
	'mode' => ['currentModeMask', 'keyModeMask']  // Passing multiple mode keys
]);

$iteratorManager->Filesystem('/path/to/files', [
	'mode' => ['currentModeMask', 'followSymlinks']  // Passing multiple mode keys
]);
