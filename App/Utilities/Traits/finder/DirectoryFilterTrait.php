<?php

namespace App\Utilities\Traits\Finder;

use SplFileInfo;  // Import native SplFileInfo

trait DirectoryFilterTrait
{
    /**
     * Filter files or directories by name
     *
     * @param SplFileInfo $fileInfo
     * @param array $criteria
     * @return bool
     */
    protected function filterByName(SplFileInfo $fileInfo, array $criteria): bool
    {
        if (isset($criteria['name'])) {
            $nameToMatch = $criteria['name'];
            return $fileInfo->getFilename() === $nameToMatch;
        }

        return true; // No name criteria, don't filter out
    }

    /**
     * Filter files or directories by last modified time
     *
     * @param SplFileInfo $fileInfo
     * @param array $criteria
     * @return bool
     */
    protected function filterByLastModified(SplFileInfo $fileInfo, array $criteria): bool
    {
        if (isset($criteria['lastModified'])) {
            $minTime = $criteria['lastModified'];
            return $fileInfo->getMTime() >= $minTime;
        }

        return true; // No lastModified criteria, don't filter out
    }

    /**
     * Filter files or directories by size
     *
     * @param SplFileInfo $fileInfo
     * @param array $criteria
     * @return bool
     */
    protected function filterBySize(SplFileInfo $fileInfo, array $criteria): bool
    {
        if (isset($criteria['size'])) {
            $minSize = $criteria['size'];
            return $fileInfo->getSize() >= $minSize;
        }

        return true; // No size criteria, don't filter out
    }

    /**
     * Filter files by extension
     *
     * @param SplFileInfo $fileInfo
     * @param array $criteria
     * @return bool
     */
    protected function filterByExtension(SplFileInfo $fileInfo, array $criteria): bool
    {
        if (isset($criteria['extension'])) {
            $extension = $criteria['extension'];
            return $fileInfo->getExtension() === $extension;
        }

        return true; // No extension criteria, don't filter out
    }

    /**
     * Filter files by creation time
     *
     * @param SplFileInfo $fileInfo
     * @param array $criteria
     * @return bool
     */
    protected function filterByCreationTime(SplFileInfo $fileInfo, array $criteria): bool
    {
        if (isset($criteria['createdAfter'])) {
            $createdAfter = $criteria['createdAfter'];
            return $fileInfo->getCTime() >= $createdAfter;
        }

        return true; // No createdAfter criteria, don't filter out
    }

    /**
     * Filter files or directories by visibility (if hidden or not)
     *
     * @param SplFileInfo $fileInfo
     * @param array $criteria
     * @return bool
     */
    protected function filterByVisibility(SplFileInfo $fileInfo, array $criteria): bool
    {
        if (isset($criteria['hidden'])) {
            $isHidden = $criteria['hidden'];
            // Check if the file is hidden (starts with a dot)
            $filename = $fileInfo->getFilename();
            return ($isHidden && $filename[0] === '.') || (!$isHidden && $filename[0] !== '.');
        }

        return true; // No hidden criteria, don't filter out
    }
}
