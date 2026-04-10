<?php

declare(strict_types=1);

namespace App\Drivers\Session;

use App\Contracts\Session\SessionDriverInterface;
use App\Utilities\Managers\FileManager;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\ManipulationTrait;
use SessionHandler;

class FileSessionDriver extends SessionHandler implements SessionDriverInterface
{
    use ArrayTrait, ManipulationTrait;

    public function __construct(
        private readonly FileManager $fileManager,
        private readonly string $path
    ) {
    }

    public function driverName(): string
    {
        return 'file';
    }

    public function capabilities(): array
    {
        return [
            'extension' => true,
            'persistent' => true,
            'garbage_collection' => true,
        ];
    }

    public function supports(string $feature): bool
    {
        return ($this->capabilities()[$feature] ?? null) === true;
    }

    public function open(string $savePath, string $sessionName): bool
    {
        return $this->fileManager->isDirectory($this->path)
            || $this->fileManager->createDirectory($this->path, 0777, true);
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $contents = $this->fileManager->readContents($this->sessionFile($id));

        return $contents ?? '';
    }

    public function write(string $id, string $data): bool
    {
        return $this->fileManager->writeContents($this->sessionFile($id), $data) !== false;
    }

    public function destroy(string $id): bool
    {
        $file = $this->sessionFile($id);

        return !$this->fileManager->fileExists($file) || $this->fileManager->deleteFile($file);
    }

    public function gc(int $max_lifetime): int|false
    {
        $deleted = 0;
        $files = glob($this->path . DIRECTORY_SEPARATOR . '*.session') ?: [];
        $cutoff = time() - $max_lifetime;

        foreach ($files as $file) {
            $modified = @filemtime($file);

            if ($modified !== false && $modified < $cutoff && $this->fileManager->deleteFile($file)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    private function sessionFile(string $id): string
    {
        return $this->path . DIRECTORY_SEPARATOR . $this->trimString($id) . '.session';
    }
}
