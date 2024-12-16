<?php

namespace App\Utilities\Traits\Criteria;

/**
 * Trait DirectoryCriteriaTrait
 *
 * Provides filtering utilities for directory operations. This trait includes
 * methods for filtering directory entries based on various criteria such as path,
 * name, type, ownership, permissions, timestamps, depth, and symbolic links.
 *
 * **Usage Example**:
 * ```php
 * use App\Utilities\Traits\Criteria\DirectoryCriteriaTrait;
 *
 * $finder = new DirectoryIterator('/path/to/scan');
 * foreach ($finder as $fileInfo) {
 *     if ($this->filterByName($fileInfo, 'exampleDir')) {
 *         // Process matched directories
 *     }
 * }
 * ```
 */
trait DirectoryCriteriaTrait
{
    /**
     * Filter directories by path.
     *
     * @param \SplFileInfo $fileInfo Directory information object.
     * @param string $path The path to match.
     * @return bool True if the directory path matches, otherwise false.
     */
    protected function filterByPath($fileInfo, string $path): bool
    {
        return strpos($fileInfo->getRealPath(), $path) !== false;
    }

    /**
     * Filter directories by name.
     *
     * @param \SplFileInfo $fileInfo Directory information object.
     * @param string $name The name to match.
     * @param bool $caseSensitive Whether the comparison is case-sensitive (default: true).
     * @return bool True if the directory name matches, otherwise false.
     */
    protected function filterByName($fileInfo, string $name, bool $caseSensitive = true): bool
    {
        $directoryName = $fileInfo->getFilename();
        return $caseSensitive ? $directoryName === $name : strcasecmp($directoryName, $name) === 0;
    }

    /**
     * Filter items by type (directories only).
     *
     * @param \SplFileInfo $fileInfo Directory information object.
     * @param string $type The type to match (default: 'directory').
     * @return bool True if the item is a directory, otherwise false.
     */
    protected function filterByType($fileInfo, string $type = 'directory'): bool
    {
        return $fileInfo->isDir();
    }

    /**
     * Filter directories by owner ID.
     *
     * @param \SplFileInfo $fileInfo Directory information object.
     * @param int $owner The owner ID to match.
     * @return bool True if the owner ID matches, otherwise false.
     */
    protected function filterByOwner($fileInfo, int $owner): bool
    {
        return $fileInfo->getOwner() === $owner;
    }

    /**
     * Filter directories by group ID.
     *
     * @param \SplFileInfo $fileInfo Directory information object.
     * @param int $group The group ID to match.
     * @return bool True if the group ID matches, otherwise false.
     */
    protected function filterByGroup($fileInfo, int $group): bool
    {
        return $fileInfo->getGroup() === $group;
    }

    /**
     * Filter directories by permissions.
     *
     * @param \SplFileInfo $fileInfo Directory information object.
     * @param int $permissions The permissions to match.
     * @return bool True if the directory matches the specified permissions, otherwise false.
     */
    protected function filterByPermissions($fileInfo, int $permissions): bool
    {
        return ($fileInfo->getPerms() & $permissions) === $permissions;
    }

    /**
     * Filter directories by last modified time.
     *
     * @param \SplFileInfo $fileInfo Directory information object.
     * @param int $timestamp The minimum last modified timestamp.
     * @return bool True if the directory was modified after or at the timestamp, otherwise false.
     */
    protected function filterByModifiedTime($fileInfo, int $timestamp): bool
    {
        return $fileInfo->getMTime() >= $timestamp;
    }

    /**
     * Filter directories by last accessed time.
     *
     * @param \SplFileInfo $fileInfo Directory information object.
     * @param int $timestamp The minimum last accessed timestamp.
     * @return bool True if the directory was accessed after or at the timestamp, otherwise false.
     */
    protected function filterByAccessedTime($fileInfo, int $timestamp): bool
    {
        return $fileInfo->getATime() >= $timestamp;
    }

    /**
     * Filter directories by creation time.
     *
     * @param \SplFileInfo $fileInfo Directory information object.
     * @param int $timestamp The minimum creation timestamp.
     * @return bool True if the directory was created after or at the timestamp, otherwise false.
     */
    protected function filterByCreationTime($fileInfo, int $timestamp): bool
    {
        return $fileInfo->getCTime() >= $timestamp;
    }

    /**
     * Filter directories by depth.
     *
     * @param \SplFileInfo $fileInfo Directory information object.
     * @param int $maxDepth The maximum directory depth.
     * @return bool True if the directory is within the depth, otherwise false.
     */
    protected function filterByDepth($fileInfo, int $maxDepth): bool
    {
        return $this->iteratorManager->getDepth() <= $maxDepth;
    }

    /**
     * Filter symbolic links.
     *
     * @param \SplFileInfo $fileInfo Directory information object.
     * @return bool True if the directory is a symbolic link, otherwise false.
     */
    protected function filterBySymlink($fileInfo): bool
    {
        return $fileInfo->isLink();
    }

    /**
     * Filter executable directories.
     *
     * @param \SplFileInfo $fileInfo Directory information object.
     * @return bool True if the directory is executable, otherwise false.
     */
    protected function filterByExecutable($fileInfo): bool
    {
        return $fileInfo->isExecutable();
    }

    /**
     * Filter writable directories.
     *
     * @param \SplFileInfo $fileInfo Directory information object.
     * @return bool True if the directory is writable, otherwise false.
     */
    protected function filterByWritable($fileInfo): bool
    {
        return $fileInfo->isWritable();
    }

    /**
     * Filter readable directories.
     *
     * @param \SplFileInfo $fileInfo Directory information object.
     * @return bool True if the directory is readable, otherwise false.
     */
    protected function filterByReadable($fileInfo): bool
    {
        return $fileInfo->isReadable();
    }

    /**
     * Filter directories by name pattern.
     *
     * @param \SplFileInfo $fileInfo Directory information object.
     * @param string $pattern The regex pattern to match the directory name.
     * @return bool True if the directory name matches the pattern, otherwise false.
     */
    protected function filterByPatternName($fileInfo, string $pattern): bool
    {
        return preg_match($pattern, $fileInfo->getFilename()) === 1;
    }

    /**
     * Filter directories by path pattern.
     *
     * @param \SplFileInfo $fileInfo Directory information object.
     * @param string $pattern The regex pattern to match the directory path.
     * @return bool True if the directory path matches the pattern, otherwise false.
     */
    protected function filterByPatternPath($fileInfo, string $pattern): bool
    {
        return preg_match($pattern, $fileInfo->getRealPath()) === 1;
    }
}
