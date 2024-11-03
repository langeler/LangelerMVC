<?php

namespace App\Utilities\Traits\Sort;

trait FileSortTrait
{
	protected function sortByName($a, $b): int
	{
		return strcmp($a->getFilename(), $b->getFilename());
	}

	protected function sortByPath($a, $b): int
	{
		return strcmp($a->getRealPath(), $b->getRealPath());
	}

	protected function sortBySize($a, $b): int
	{
		return $a->getSize() <=> $b->getSize();
	}

	protected function sortByExtension($a, $b): int
	{
		return strcmp($a->getExtension(), $b->getExtension());
	}

	protected function sortByModifiedTime($a, $b): int
	{
		return $a->getMTime() <=> $b->getMTime();
	}

	protected function sortByAccessedTime($a, $b): int
	{
		return $a->getATime() <=> $b->getATime();
	}

	protected function sortByCreationTime($a, $b): int
	{
		return $a->getCTime() <=> $b->getCTime();
	}

	protected function sortByPermissions($a, $b): int
	{
		return $a->getPerms() <=> $b->getPerms();
	}

	protected function sortByOwner($a, $b): int
	{
		return $a->getOwner() <=> $b->getOwner();
	}

	protected function sortByGroup($a, $b): int
	{
		return $a->getGroup() <=> $b->getGroup();
	}

	protected function sortByDepth($a, $b): int
	{
		// Assumes that depth can be retrieved from the iterator manager
		return $this->iteratorManager->getDepth($a) <=> $this->iteratorManager->getDepth($b);
	}
}
