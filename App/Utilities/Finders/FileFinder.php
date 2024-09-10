<?php

namespace App\Utilities\Finders;

use App\Abstracts\Finder;
use App\Exceptions\FileFinderException;

class FileFinder extends Finder
{
	/**
	 * Search for files based on criteria.
	 *
	 * @param array $criteria Criteria for finding files.
	 * @return array The list of found files.
	 */
	public function find(array $criteria = []): array
	{
		$iterator = $this->iteratorManager->createRecursiveDirectoryIterator($this->searchPath);
		return iterator_to_array($this->applyFilters($iterator, $criteria));
	}

	/**
	 * Find files by extension.
	 *
	 * @param string $extension The file extension to filter by.
	 * @return array The list of found files.
	 */
	public function findByExtension(string $extension): array
	{
		return $this->find(['extension' => $extension]);
	}

	/**
	 * Find files by name.
	 *
	 * @param string $name The file name to search for.
	 * @return array The list of found files.
	 */
	public function findByName(string $name): array
	{
		return $this->find(['name' => $name]);
	}

	/**
	 * Find files with a size range.
	 *
	 * @param int $minSize Minimum file size in bytes.
	 * @param int $maxSize Maximum file size in bytes.
	 * @return array The list of found files.
	 */
	public function findBySizeRange(int $minSize, int $maxSize): array
	{
		return $this->find(['minSize' => $minSize, 'maxSize' => $maxSize]);
	}

	/**
	 * Find files that were last modified after a specific timestamp.
	 *
	 * @param int $timestamp The last modified timestamp.
	 * @return array The list of found files.
	 */
	public function findByLastModified(int $timestamp): array
	{
		return $this->find(['lastModified' => $timestamp]);
	}

	/**
	 * Find files using a regex pattern on file names.
	 *
	 * @param string $pattern The regex pattern.
	 * @return array The list of found files.
	 */
	public function findByRegexPattern(string $pattern): array
	{
		$iterator = $this->iteratorManager->createRecursiveDirectoryIterator($this->searchPath);
		$regexIterator = $this->iteratorManager->createRegexIterator($iterator, $pattern);
		return iterator_to_array($regexIterator);
	}

	/**
	 * Find files within a directory with a depth limit.
	 *
	 * @param int $depth The maximum depth to search.
	 * @return array The list of found files.
	 */
	public function findWithDepthLimit(int $depth): array
	{
		$iterator = $this->iteratorManager->createRecursiveDirectoryIterator($this->searchPath);
		$limitedIterator = new \LimitIterator(new \RecursiveIteratorIterator($iterator), 0, $depth);
		return iterator_to_array($limitedIterator);
	}

	/**
	 * Find files by multiple criteria (extension, size, etc.).
	 *
	 * @param array $criteria The criteria for finding files.
	 * @return array The list of found files.
	 */
	public function findWithMultipleCriteria(array $criteria): array
	{
		return $this->find($criteria);
	}
}
