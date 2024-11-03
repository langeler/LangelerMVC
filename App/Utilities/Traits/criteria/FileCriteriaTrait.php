<?php

namespace App\Utilities\Traits\Criteria;

trait FileCriteriaTrait
{
    protected function filterByPath($fileInfo, string $path): bool
    {
        // Check if the file path matches specified criteria
        return strpos($fileInfo->getRealPath(), $path) !== false;
    }

    protected function filterByName($fileInfo, string $name, bool $caseSensitive = true): bool
    {
        // Check if the file name matches specified criteria
        $fileName = $fileInfo->getFilename();
        return $caseSensitive ? $fileName === $name : strcasecmp($fileName, $name) === 0;
    }
    protected function filterByExtension($fileInfo, string $extension): bool
    {
        // Check if the file has a specific extension
        return $fileInfo->getExtension() === ltrim($extension, '.');
    }

    protected function filterBySize($fileInfo, int $size): bool
    {
        // Check if the file size meets specified criteria
        return $fileInfo->getSize() >= $size;
    }

    protected function filterByPermissions($fileInfo, int $permissions): bool
    {
        // Check if the file has specific permissions
        return ($fileInfo->getPerms() & $permissions) === $permissions;
    }

    protected function filterByOwner($fileInfo, int $owner): bool
    {
        // Check if the file owner matches specified ID
        return $fileInfo->getOwner() === $owner;
    }

    protected function filterByGroup($fileInfo, int $group): bool
    {
        // Check if the file group matches specified ID
        return $fileInfo->getGroup() === $group;
    }

    protected function filterByModifiedTime($fileInfo, int $timestamp): bool
    {
        // Check if the file was modified after a specific timestamp
        return $fileInfo->getMTime() >= $timestamp;
    }

    protected function filterByAccessedTime($fileInfo, int $timestamp): bool
    {
        // Check if the file was accessed after a specific timestamp
        return $fileInfo->getATime() >= $timestamp;
    }

    protected function filterByCreationTime($fileInfo, int $timestamp): bool
    {
        // Check if the file was created after a specific timestamp
        return $fileInfo->getCTime() >= $timestamp;
    }

    protected function filterBySymlink($fileInfo): bool
    {
        // Check if the file is a symbolic link
        return $fileInfo->isLink();
    }

    protected function filterByDepth($fileInfo, int $maxDepth): bool
    {
        // Check if the file is within the specified depth
        return $this->iteratorManager->getDepth() <= $maxDepth;
    }

    protected function filterByExecutable($fileInfo): bool
    {
        // Check if the file is executable
        return $fileInfo->isExecutable();
    }

    protected function filterByWritable($fileInfo): bool
    {
        // Check if the file is writable
        return $fileInfo->isWritable();
    }

    protected function filterByReadable($fileInfo): bool
    {
        // Check if the file is readable
        return $fileInfo->isReadable();
    }

    // FileFinder: filter by filename pattern
    protected function filterByPatternName($fileInfo, string $pattern): bool
    {
        return preg_match($pattern, $fileInfo->getFilename()) === 1;
    }

    // FileFinder: filter by file extension pattern
    protected function filterByPatternExtension($fileInfo, string $pattern): bool
    {
        return preg_match($pattern, $fileInfo->getExtension()) === 1;
    }

    // FileFinder: filter by file path pattern
    protected function filterByPatternPath($fileInfo, string $pattern): bool
    {
        return preg_match($pattern, $fileInfo->getRealPath()) === 1;
    }
}
