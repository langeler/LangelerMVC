<?php

namespace App\Utilities\Traits\Criteria;

/**
 * Trait FileCriteriaTrait
 *
 * Provides filtering utilities for file operations. This trait includes
 * methods for filtering files based on various criteria such as path, name,
 * extension, size, permissions, ownership, timestamps, symbolic links, and patterns.
 *
 * These methods are intended to be used in file management utilities or file
 * search/filtering operations.
 *
 * **Usage Example**:
 * ```php
 * use App\Utilities\Traits\Criteria\FileCriteriaTrait;
 *
 * $finder = new DirectoryIterator('/path/to/files');
 * foreach ($finder as $fileInfo) {
 *     if ($this->filterByExtension($fileInfo, 'txt')) {
 *         // Process .txt files
 *     }
 * }
 * ```
 */
trait FileCriteriaTrait
{
    /**
     * Filter files by path.
     *
     * @param \SplFileInfo $fileInfo File information object.
     * @param string $path The path to match.
     * @return bool True if the file path matches, otherwise false.
     */
    protected function filterByPath($fileInfo, string $path): bool
    {
        return strpos($fileInfo->getRealPath(), $path) !== false;
    }

    /**
     * Filter files by name.
     *
     * @param \SplFileInfo $fileInfo File information object.
     * @param string $name The name to match.
     * @param bool $caseSensitive Whether the comparison is case-sensitive (default: true).
     * @return bool True if the file name matches, otherwise false.
     */
    protected function filterByName($fileInfo, string $name, bool $caseSensitive = true): bool
    {
        $fileName = $fileInfo->getFilename();
        return $caseSensitive ? $fileName === $name : strcasecmp($fileName, $name) === 0;
    }

    /**
     * Filter files by extension.
     *
     * @param \SplFileInfo $fileInfo File information object.
     * @param string $extension The extension to match (e.g., 'txt', 'jpg').
     * @return bool True if the file has the specified extension, otherwise false.
     */
    protected function filterByExtension($fileInfo, string $extension): bool
    {
        return $fileInfo->getExtension() === ltrim($extension, '.');
    }

    /**
     * Filter files by size.
     *
     * @param \SplFileInfo $fileInfo File information object.
     * @param int $size The minimum file size in bytes.
     * @return bool True if the file size is greater than or equal to the specified size, otherwise false.
     */
    protected function filterBySize($fileInfo, int $size): bool
    {
        return $fileInfo->getSize() >= $size;
    }

    /**
     * Filter files by permissions.
     *
     * @param \SplFileInfo $fileInfo File information object.
     * @param int $permissions The permissions to match (e.g., 0755).
     * @return bool True if the file has the specified permissions, otherwise false.
     */
    protected function filterByPermissions($fileInfo, int $permissions): bool
    {
        return ($fileInfo->getPerms() & $permissions) === $permissions;
    }

    /**
     * Filter files by owner ID.
     *
     * @param \SplFileInfo $fileInfo File information object.
     * @param int $owner The owner ID to match.
     * @return bool True if the file owner matches the specified ID, otherwise false.
     */
    protected function filterByOwner($fileInfo, int $owner): bool
    {
        return $fileInfo->getOwner() === $owner;
    }

    /**
     * Filter files by group ID.
     *
     * @param \SplFileInfo $fileInfo File information object.
     * @param int $group The group ID to match.
     * @return bool True if the file group matches the specified ID, otherwise false.
     */
    protected function filterByGroup($fileInfo, int $group): bool
    {
        return $fileInfo->getGroup() === $group;
    }

    /**
     * Filter files by last modified time.
     *
     * @param \SplFileInfo $fileInfo File information object.
     * @param int $timestamp The minimum last modified timestamp.
     * @return bool True if the file was modified on or after the specified timestamp, otherwise false.
     */
    protected function filterByModifiedTime($fileInfo, int $timestamp): bool
    {
        return $fileInfo->getMTime() >= $timestamp;
    }

    /**
     * Filter files by last accessed time.
     *
     * @param \SplFileInfo $fileInfo File information object.
     * @param int $timestamp The minimum last accessed timestamp.
     * @return bool True if the file was accessed on or after the specified timestamp, otherwise false.
     */
    protected function filterByAccessedTime($fileInfo, int $timestamp): bool
    {
        return $fileInfo->getATime() >= $timestamp;
    }

    /**
     * Filter files by creation time.
     *
     * @param \SplFileInfo $fileInfo File information object.
     * @param int $timestamp The minimum creation timestamp.
     * @return bool True if the file was created on or after the specified timestamp, otherwise false.
     */
    protected function filterByCreationTime($fileInfo, int $timestamp): bool
    {
        return $fileInfo->getCTime() >= $timestamp;
    }

    /**
     * Filter symbolic links.
     *
     * @param \SplFileInfo $fileInfo File information object.
     * @return bool True if the file is a symbolic link, otherwise false.
     */
    protected function filterBySymlink($fileInfo): bool
    {
        return $fileInfo->isLink();
    }

    /**
     * Filter files by directory depth.
     *
     * @param \SplFileInfo $fileInfo File information object.
     * @param int $maxDepth The maximum depth.
     * @return bool True if the file is within the specified depth, otherwise false.
     */
    protected function filterByDepth($fileInfo, int $maxDepth): bool
    {
        return $this->iteratorManager->getDepth() <= $maxDepth;
    }

    /**
     * Filter executable files.
     *
     * @param \SplFileInfo $fileInfo File information object.
     * @return bool True if the file is executable, otherwise false.
     */
    protected function filterByExecutable($fileInfo): bool
    {
        return $fileInfo->isExecutable();
    }

    /**
     * Filter writable files.
     *
     * @param \SplFileInfo $fileInfo File information object.
     * @return bool True if the file is writable, otherwise false.
     */
    protected function filterByWritable($fileInfo): bool
    {
        return $fileInfo->isWritable();
    }

    /**
     * Filter readable files.
     *
     * @param \SplFileInfo $fileInfo File information object.
     * @return bool True if the file is readable, otherwise false.
     */
    protected function filterByReadable($fileInfo): bool
    {
        return $fileInfo->isReadable();
    }

    /**
     * Filter files by filename pattern.
     *
     * @param \SplFileInfo $fileInfo File information object.
     * @param string $pattern The regex pattern to match the filename.
     * @return bool True if the file name matches the pattern, otherwise false.
     */
    protected function filterByPatternName($fileInfo, string $pattern): bool
    {
        return preg_match($pattern, $fileInfo->getFilename()) === 1;
    }

    /**
     * Filter files by extension pattern.
     *
     * @param \SplFileInfo $fileInfo File information object.
     * @param string $pattern The regex pattern to match the file extension.
     * @return bool True if the file extension matches the pattern, otherwise false.
     */
    protected function filterByPatternExtension($fileInfo, string $pattern): bool
    {
        return preg_match($pattern, $fileInfo->getExtension()) === 1;
    }

    /**
     * Filter files by path pattern.
     *
     * @param \SplFileInfo $fileInfo File information object.
     * @param string $pattern The regex pattern to match the file path.
     * @return bool True if the file path matches the pattern, otherwise false.
     */
    protected function filterByPatternPath($fileInfo, string $pattern): bool
    {
        return preg_match($pattern, $fileInfo->getRealPath()) === 1;
    }
}
