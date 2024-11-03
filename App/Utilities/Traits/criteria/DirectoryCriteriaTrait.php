<?php

namespace App\Utilities\Traits\Criteria;

trait DirectoryCriteriaTrait
{
    protected function filterByPath($fileInfo, string $path): bool
    {
        // Check if the directory path matches specified criteria
        return strpos($fileInfo->getRealPath(), $path) !== false;
    }

    protected function filterByName($fileInfo, string $name, bool $caseSensitive = true): bool
    {
        // Check if the directory name matches specified criteria
        $directoryName = $fileInfo->getFilename();
        return $caseSensitive ? $directoryName === $name : strcasecmp($directoryName, $name) === 0;
    }

    protected function filterByType($fileInfo, string $type = 'directory'): bool
    {
        // Ensure the item is a directory
        return $fileInfo->isDir();
    }

    protected function filterByOwner($fileInfo, int $owner): bool
    {
        // Check if the directory owner matches specified ID
        return $fileInfo->getOwner() === $owner;
    }

    protected function filterByGroup($fileInfo, int $group): bool
    {
        // Check if the directory group matches specified ID
        return $fileInfo->getGroup() === $group;
    }

    protected function filterByPermissions($fileInfo, int $permissions): bool
    {
        // Check if the directory has specific permissions
        return ($fileInfo->getPerms() & $permissions) === $permissions;
    }

    protected function filterByModifiedTime($fileInfo, int $timestamp): bool
    {
        // Check if the directory was modified after a specific timestamp
        return $fileInfo->getMTime() >= $timestamp;
    }

    protected function filterByAccessedTime($fileInfo, int $timestamp): bool
    {
        // Check if the directory was accessed after a specific timestamp
        return $fileInfo->getATime() >= $timestamp;
    }

    protected function filterByCreationTime($fileInfo, int $timestamp): bool
    {
        // Check if the directory was created after a specific timestamp
        return $fileInfo->getCTime() >= $timestamp;
    }

    protected function filterByDepth($fileInfo, int $maxDepth): bool
    {
        // Check if the directory is within the specified depth
        return $this->iteratorManager->getDepth() <= $maxDepth;
    }

    protected function filterBySymlink($fileInfo): bool
    {
        // Check if the directory is a symbolic link
        return $fileInfo->isLink();
    }

    protected function filterByExecutable($fileInfo): bool
    {
        // Check if the directory is executable
        return $fileInfo->isExecutable();
    }

    protected function filterByWritable($fileInfo): bool
    {
        // Check if the directory is writable
        return $fileInfo->isWritable();
    }

    protected function filterByReadable($fileInfo): bool
    {
        // Check if the directory is readable
        return $fileInfo->isReadable();
    }

    // DirectoryFinder: filter by directory name pattern
    protected function filterByPatternName($fileInfo, string $pattern): bool
    {
        return preg_match($pattern, $fileInfo->getFilename()) === 1;
    }

    // DirectoryFinder: filter by directory path pattern
    protected function filterByPatternPath($fileInfo, string $pattern): bool
    {
        return preg_match($pattern, $fileInfo->getRealPath()) === 1;
    }
}
