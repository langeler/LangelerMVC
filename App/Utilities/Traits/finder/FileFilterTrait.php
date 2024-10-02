<?php

namespace App\Utilities\Traits\Finder;

use SplFileInfo; // Import native SplFileInfo

trait FileFilterTrait
{
    /**
     * Filter files by extension.
     *
     * @param SplFileInfo $fileInfo
     * @param array $criteria
     * @return bool
     */
    protected function filterByExtension(SplFileInfo $fileInfo, array $criteria): bool
    {
        if (isset($criteria['extension'])) {
            return $fileInfo->getExtension() === $criteria['extension'];
        }

        return true; // No extension criteria, don't filter out
    }

    /**
     * Filter files by name.
     *
     * @param SplFileInfo $fileInfo
     * @param array $criteria
     * @return bool
     */
    protected function filterByName(SplFileInfo $fileInfo, array $criteria): bool
    {
        if (isset($criteria['name'])) {
            return $fileInfo->getFilename() === $criteria['name'];
        }

        return true; // No name criteria, don't filter out
    }

    /**
     * Filter files by size.
     *
     * @param SplFileInfo $fileInfo
     * @param array $criteria
     * @return bool
     */
    protected function filterBySize(SplFileInfo $fileInfo, array $criteria): bool
    {
        if (isset($criteria['minSize']) && $fileInfo->getSize() < $criteria['minSize']) {
            return false;
        }

        if (isset($criteria['maxSize']) && $fileInfo->getSize() > $criteria['maxSize']) {
            return false;
        }

        return true; // If size criteria is not set or matches, don't filter out
    }

    /**
     * Filter files by last modified time.
     *
     * @param SplFileInfo $fileInfo
     * @param array $criteria
     * @return bool
     */
    protected function filterByLastModified(SplFileInfo $fileInfo, array $criteria): bool
    {
        if (isset($criteria['lastModified'])) {
            return $fileInfo->getMTime() >= $criteria['lastModified'];
        }

        return true; // No lastModified criteria, don't filter out
    }

    /**
     * Filter files by creation time.
     *
     * @param SplFileInfo $fileInfo
     * @param array $criteria
     * @return bool
     */
    protected function filterByCreationTime(SplFileInfo $fileInfo, array $criteria): bool
    {
        if (isset($criteria['createdAfter'])) {
            return $fileInfo->getCTime() >= $criteria['createdAfter'];
        }

        return true; // No creation time criteria, don't filter out
    }
}
