<?php

namespace App\Utilities\Traits\Sort;

/**
 * Trait FileSortTrait
 *
 * Provides utility methods for sorting files based on various properties.
 * Each method is designed to be used as a callback function with sorting utilities
 * like `usort()` to organize files according to specific criteria.
 *
 * **Usage Example**:
 * ```php
 * use App\Utilities\Traits\Sort\FileSortTrait;
 *
 * $files = [new SplFileInfo('file1.txt'), new SplFileInfo('file2.txt')];
 * usort($files, [$this, 'sortByName']);
 * ```
 */
trait FileSortTrait
{
	/**
	 * Sort files by name (case-sensitive).
	 *
	 * @param \SplFileInfo $a First file object.
	 * @param \SplFileInfo $b Second file object.
	 * @return int Negative if $a's name comes before $b's, zero if they are equal, positive if $a's name comes after $b's.
	 */
	protected function sortByName($a, $b): int
	{
		return strcmp($a->getFilename(), $b->getFilename());
	}

	/**
	 * Sort files by full file path.
	 *
	 * @param \SplFileInfo $a First file object.
	 * @param \SplFileInfo $b Second file object.
	 * @return int Negative if $a's path comes before $b's, zero if they are equal, positive if $a's path comes after $b's.
	 */
	protected function sortByPath($a, $b): int
	{
		return strcmp($a->getRealPath(), $b->getRealPath());
	}

	/**
	 * Sort files by file size.
	 *
	 * @param \SplFileInfo $a First file object.
	 * @param \SplFileInfo $b Second file object.
	 * @return int Negative if $a is smaller, zero if they are equal in size, positive if $a is larger.
	 */
	protected function sortBySize($a, $b): int
	{
		return $a->getSize() <=> $b->getSize();
	}

	/**
	 * Sort files by their extension (case-sensitive).
	 *
	 * @param \SplFileInfo $a First file object.
	 * @param \SplFileInfo $b Second file object.
	 * @return int Negative if $a's extension comes before $b's, zero if they are equal, positive if $a's extension comes after $b's.
	 */
	protected function sortByExtension($a, $b): int
	{
		return strcmp($a->getExtension(), $b->getExtension());
	}

	/**
	 * Sort files by the last modified time.
	 *
	 * @param \SplFileInfo $a First file object.
	 * @param \SplFileInfo $b Second file object.
	 * @return int Negative if $a was modified earlier, zero if they were modified at the same time, positive if $a was modified later.
	 */
	protected function sortByModifiedTime($a, $b): int
	{
		return $a->getMTime() <=> $b->getMTime();
	}

	/**
	 * Sort files by the last accessed time.
	 *
	 * @param \SplFileInfo $a First file object.
	 * @param \SplFileInfo $b Second file object.
	 * @return int Negative if $a was accessed earlier, zero if they were accessed at the same time, positive if $a was accessed later.
	 */
	protected function sortByAccessedTime($a, $b): int
	{
		return $a->getATime() <=> $b->getATime();
	}

	/**
	 * Sort files by creation time.
	 *
	 * @param \SplFileInfo $a First file object.
	 * @param \SplFileInfo $b Second file object.
	 * @return int Negative if $a was created earlier, zero if they were created at the same time, positive if $a was created later.
	 */
	protected function sortByCreationTime($a, $b): int
	{
		return $a->getCTime() <=> $b->getCTime();
	}

	/**
	 * Sort files by permissions.
	 *
	 * @param \SplFileInfo $a First file object.
	 * @param \SplFileInfo $b Second file object.
	 * @return int Negative if $a has fewer permissions, zero if they are equal, positive if $a has more permissions.
	 */
	protected function sortByPermissions($a, $b): int
	{
		return $a->getPerms() <=> $b->getPerms();
	}

	/**
	 * Sort files by owner ID.
	 *
	 * @param \SplFileInfo $a First file object.
	 * @param \SplFileInfo $b Second file object.
	 * @return int Negative if $a's owner ID is smaller, zero if they are equal, positive if $a's owner ID is greater.
	 */
	protected function sortByOwner($a, $b): int
	{
		return $a->getOwner() <=> $b->getOwner();
	}

	/**
	 * Sort files by group ID.
	 *
	 * @param \SplFileInfo $a First file object.
	 * @param \SplFileInfo $b Second file object.
	 * @return int Negative if $a's group ID is smaller, zero if they are equal, positive if $a's group ID is greater.
	 */
	protected function sortByGroup($a, $b): int
	{
		return $a->getGroup() <=> $b->getGroup();
	}
}
