<?php

namespace App\Utilities\Finders;

use App\Abstracts\Finder;
use App\Exceptions\DirectoryFinderException;

class DirectoryFinder extends Finder
{
	/**
	 * Search for directories based on criteria.
	 *
	 * @param array $criteria Criteria for finding directories.
	 * @return array The list of found directories.
	 */
	public function find(array $criteria = []): array
	{
		$iterator = $this->iteratorManager->createRecursiveDirectoryIterator($this->searchPath);
		return iterator_to_array($this->applyFilters($iterator, $criteria));
	}

	/**
	 * Find directories by name.
	 *
	 * @param string $name The directory name to search for.
	 * @return array The list of found directories.
	 */
	public function findByName(string $name): array
	{
		return $this->find(['name' => $name]);
	}

	/**
	 * Find directories by permissions.
	 *
	 * @param int $permissions The directory permissions to filter by.
	 * @return array The list of found directories.
	 */
	public function findByPermissions(int $permissions): array
	{
		return $this->find(['permissions' => $permissions]);
	}

	/**
	 * Find non-empty directories.
	 *
	 * @return array The list of non-empty directories.
	 */
	public function findNonEmptyDirectories(): array
	{
		return $this->find(['nonEmpty' => true]);
	}

	/**
	 * Find directories using a regex pattern on directory names.
	 *
	 * @param string $pattern The regex pattern.
	 * @return array The list of found directories.
	 */
	public function findByRegexPattern(string $pattern): array
	{
		$iterator = $this->iteratorManager->createRecursiveDirectoryIterator($this->searchPath);
		$regexIterator = $this->iteratorManager->createRegexIterator($iterator, $pattern);
		return iterator_to_array($regexIterator);
	}

	/**
	 * Find directories within a directory with a depth limit.
	 *
	 * @param int $depth The maximum depth to search.
	 * @return array The list of found directories.
	 */
	public function findWithDepthLimit(int $depth): array
	{
		$iterator = $this->iteratorManager->createRecursiveDirectoryIterator($this->searchPath);
		$limitedIterator = new \LimitIterator(new \RecursiveIteratorIterator($iterator), 0, $depth);
		return iterator_to_array($limitedIterator);
	}

	/**
	 * Find directories by multiple criteria (name, permissions, etc.).
	 *
	 * @param array $criteria The criteria for finding directories.
	 * @return array The list of found directories.
	 */
	public function findWithMultipleCriteria(array $criteria): array
	{
		return $this->find($criteria);
	}
}
