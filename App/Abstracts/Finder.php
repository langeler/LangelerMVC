<?php

namespace App\Abstracts;

abstract class Finder
{
	/**
	 * @var string $searchPath The path to search files or directories.
	 */
	protected string $searchPath;

	/**
	 * @var IteratorManager $iteratorManager Manages different iterator functionalities.
	 */
	protected IteratorManager $iteratorManager;

	/**
	 * Finder constructor.
	 *
	 * @param string $path The base path for the search.
	 * @param IteratorManager $iteratorManager The iterator manager instance.
	 */
	public function __construct(string $path, IteratorManager $iteratorManager)
	{
		$this->searchPath = $path;
		$this->iteratorManager = $iteratorManager;
	}

	/**
	 * General find method to be implemented by extending classes.
	 *
	 * @param array $criteria Criteria to filter the search results.
	 * @return array The filtered search results.
	 */
	abstract public function find(array $criteria = []): array;

	/**
	 * Apply filtering based on the criteria provided.
	 *
	 * @param \Traversable $iterator The iterator to be filtered.
	 * @param array $criteria The criteria for filtering.
	 * @return \Traversable The filtered iterator.
	 */
	protected function applyFilters(\Traversable $iterator, array $criteria): \Traversable
	{
		return $this->iteratorManager->createRecursiveCallbackFilterIterator($iterator, function ($item) use ($criteria) {
			return $this->processCriteria($item, $criteria);
		});
	}

	/**
	 * Process and validate the criteria for filtering.
	 *
	 * @param object $item The current item in the iteration.
	 * @param array $criteria The filtering criteria.
	 * @return bool True if the item meets all criteria, false otherwise.
	 */
	protected function processCriteria($item, array $criteria): bool
	{
		// Check file extension
		if (isset($criteria['extension']) && $item->isFile()) {
			if ($item->getExtension() !== $criteria['extension']) {
				return false;
			}
		}

		// Check file or directory name
		if (isset($criteria['name']) && $item->getFilename() !== $criteria['name']) {
			return false;
		}

		// Check size range
		if (isset($criteria['minSize']) && $item->getSize() < $criteria['minSize']) {
			return false;
		}

		if (isset($criteria['maxSize']) && $item->getSize() > $criteria['maxSize']) {
			return false;
		}

		// Check last modified time
		if (isset($criteria['lastModified']) && $item->getMTime() < $criteria['lastModified']) {
			return false;
		}

		// Check permissions
		if (isset($criteria['permissions']) && $item->getPerms() !== $criteria['permissions']) {
			return false;
		}

		// Check date range
		if (isset($criteria['minDate']) && $item->getMTime() < $criteria['minDate']) {
			return false;
		}

		if (isset($criteria['maxDate']) && $item->getMTime() > $criteria['maxDate']) {
			return false;
		}

		return true;
	}
}
