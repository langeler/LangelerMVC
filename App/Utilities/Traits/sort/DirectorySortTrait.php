<?php

namespace App\Utilities\Traits\Sort;

/**
 * Trait DirectorySortTrait
 *
 * Provides utility methods for sorting directories based on various criteria.
 * These methods are intended to be used as callback functions for array sorting methods like usort.
 */
trait DirectorySortTrait
{
	/**
	 * Sort by file name.
	 *
	 * @param \SplFileInfo $a First file object.
	 * @param \SplFileInfo $b Second file object.
	 * @return int Negative if $a comes before $b, zero if they are equal, positive if $a comes after $b.
	 */
	protected function sortByName($a, $b): int
	{
		return strcmp($a->getFilename(), $b->getFilename());
	}

	/**
	 * Sort by file path.
	 *
	 * @param \SplFileInfo $a First file object.
	 * @param \SplFileInfo $b Second file object.
	 * @return int Negative if $a comes before $b, zero if they are equal, positive if $a comes after $b.
	 */
	protected function sortByPath($a, $b): int
	{
		return strcmp($a->getRealPath(), $b->getRealPath());
	}

	/**
	 * Sort by last modified time.
	 *
	 * @param \SplFileInfo $a First file object.
	 * @param \SplFileInfo $b Second file object.
	 * @return int Negative if $a was modified earlier than $b, zero if they were modified at the same time, positive if $a was modified later than $b.
	 */
	protected function sortByModifiedTime($a, $b): int
	{
		return $a->getMTime() <=> $b->getMTime();
	}

	/**
	 * Sort by last accessed time.
	 *
	 * @param \SplFileInfo $a First file object.
	 * @param \SplFileInfo $b Second file object.
	 * @return int Negative if $a was accessed earlier than $b, zero if they were accessed at the same time, positive if $a was accessed later than $b.
	 */
	protected function sortByAccessedTime($a, $b): int
	{
		return $a->getATime() <=> $b->getATime();
	}

	/**
	 * Sort by creation time.
	 *
	 * @param \SplFileInfo $a First file object.
	 * @param \SplFileInfo $b Second file object.
	 * @return int Negative if $a was created earlier than $b, zero if they were created at the same time, positive if $a was created later than $b.
	 */
	protected function sortByCreationTime($a, $b): int
	{
		return $a->getCTime() <=> $b->getCTime();
	}

	/**
	 * Sort by file permissions.
	 *
	 * @param \SplFileInfo $a First file object.
	 * @param \SplFileInfo $b Second file object.
	 * @return int Negative if $a has fewer permissions than $b, zero if they have the same permissions, positive if $a has more permissions than $b.
	 */
	protected function sortByPermissions($a, $b): int
	{
		return $a->getPerms() <=> $b->getPerms();
	}

	/**
	 * Sort by file owner.
	 *
	 * @param \SplFileInfo $a First file object.
	 * @param \SplFileInfo $b Second file object.
	 * @return int Negative if $a's owner ID is smaller than $b's, zero if they are equal, positive if $a's owner ID is greater than $b's.
	 */
	protected function sortByOwner($a, $b): int
	{
		return $a->getOwner() <=> $b->getOwner();
	}

	/**
	 * Sort by file group.
	 *
	 * @param \SplFileInfo $a First file object.
	 * @param \SplFileInfo $b Second file object.
	 * @return int Negative if $a's group ID is smaller than $b's, zero if they are equal, positive if $a's group ID is greater than $b's.
	 */
	protected function sortByGroup($a, $b): int
	{
		return $a->getGroup() <=> $b->getGroup();
	}
}
