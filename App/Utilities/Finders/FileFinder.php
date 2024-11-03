<?php

namespace App\Utilities\Finders;

use App\Abstracts\Data\Finder;
use App\Utilities\Traits\Criteria\FileCriteriaTrait;
use App\Utilities\Traits\Sort\FileSortTrait;

class FileFinder extends Finder
{
    use FileCriteriaTrait, FileSortTrait;

    public function find(array $criteria = [], ?string $path = null, array $sort = []): array
    {
        try {
            return $this->handle($criteria, $path, $sort);
        } catch (Throwable $e) {
            throw new FinderException("Error in FileFinder find: " . $e->getMessage(), 0, $e);
        }
    }


    public function search(array $criteria = [], ?string $path = null, array $sort = []): array
    {
        try {
            return $this->searchMultipleDirectories([$this->validatePath($path ?? $this->root)], $criteria, $sort);
        } catch (Throwable $e) {
            throw new FinderException("Error during searchFiles: " . $e->getMessage(), 0, $e);
        }
    }

    public function scan(?string $path = null): array
    {
        try {
            return array_values(
                array_filter(
                    array_map(
                        fn($item) => $this->getFileInfo($item, $path),
                        scandir($this->validatePath($path ?? $this->root)) ?: throw new FinderException("Failed to scan directory")
                    )
                )
            );
        } catch (Throwable $e) {
            throw new FinderException("Error during scandir: " . $e->getMessage(), 0, $e);
        }
    }

    public function showTree(?string $path = null): void
    {
        try {
            $this->displayDirectoryTree($this->validatePath($path ?? $this->root));
        } catch (Throwable $e) {
            throw new FinderException("Error displaying file tree: " . $e->getMessage(), 0, $e);
        }
    }

    public function findByDepth(array $criteria = [], int $maxDepth = 0, ?string $path = null): array
    {
        try {
            return $this->filterWithDepthControl($criteria, $maxDepth, $this->validatePath($path ?? $this->root), []);
        } catch (Throwable $e) {
            throw new FinderException("Error filtering files by depth: " . $e->getMessage(), 0, $e);
        }
    }

    public function findByCache(array $criteria = [], ?string $path = null): array
    {
        try {
            return $this->filterWithCache($criteria, $this->validatePath($path ?? $this->root));
        } catch (Throwable $e) {
            throw new FinderException("Error during cacheFiles: " . $e->getMessage(), 0, $e);
        }
    }

    public function findByRegEx(array $criteria = [], ?string $path = null): array
    {
        try {
            return $this->filterWithRegex($criteria, $this->validatePath($path ?? $this->root));
        } catch (Throwable $e) {
            throw new FinderException("Error during regexFiles: " . $e->getMessage(), 0, $e);
        }
    }

    protected function getFileInfo(string $item, ?string $path): array
    {
        try {
            $fileInfo = $this->iteratorManager->FileInfo($this->validatePath($path ?? $this->root) . DIRECTORY_SEPARATOR . $item);
            return [
                'name' => $item,
                'realPath' => $fileInfo->getRealPath(),
                'size' => $fileInfo->getSize(),
                'permissions' => $fileInfo->getPerms(),
                'lastModified' => date("F d Y H:i:s.", $fileInfo->getMTime()),
            ];
        } catch (Throwable $e) {
            throw new FinderException("Error retrieving file info: " . $e->getMessage(), 0, $e);
        }
    }
}
