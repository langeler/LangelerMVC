<?php

namespace App\Utilities\Managers;

use App\Utilities\Finders\FileFinder;
use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Managers\FileManager;
use App\Utilities\Validation\MediaValidator;
use App\Utilities\Validation\GeneralValidator;
use App\Utilities\Sanitation\MediaSanitizer;
use App\Utilities\Sanitation\GeneralSanitizer;
use App\Helpers\TypeChecker;
use App\Helpers\ArrayHelper;
use App\Exceptions\ConfigException;
use App\Utilities\Traits\CheckerTrait;
use Throwable;

class SettingsManager
{
    use CheckerTrait;

    private string $folder;
    private array $files;
    private array $data = [];

    public function __construct(
        private DirectoryFinder $dirFinder,
        private FileFinder $fileFinder,
        private FileManager $fileManager,
        private TypeChecker $typeChecker,
        private ArrayHelper $arrayHelper,
        private MediaValidator $mediaValidator,
        private GeneralValidator $generalValidator,
        private MediaSanitizer $mediaSanitizer,
        private GeneralSanitizer $generalSanitizer
    ) {
        $this->folder = $this->validateFolder(
            $this->mediaSanitizer->sanitizeFilePathUnix(
                $this->locateConfigFolder()
            )
        );
        $this->files = $this->retrieveConfigFiles();
    }

    private function locateConfigFolder(): string
    {
        $dirs = $this->dirFinder->find(['name' => 'Config']);
        return $this->typeChecker->isArray($dirs) && !$this->typeChecker->isEmpty($dirs) && $this->typeChecker->isDirectory($dirs[0])
            ? $dirs[0]
            : throw new ConfigException("Configuration directory not found.");
    }

    private function validateFolder(string $folderPath): string
    {
        return $this->mediaValidator->validateFilePathUnix($folderPath)
            ? $folderPath
            : throw new ConfigException("Invalid configuration directory path: $folderPath");
    }

    private function retrieveConfigFiles(): array
    {
        try {
            return $this->typeChecker->isArray($files = $this->fileFinder->find(['extension' => 'php'], $this->folder)) ? $files : [];
        } catch (Throwable $e) {
            throw new ConfigException("Error retrieving configuration files.", 0, $e);
        }
    }

    private function getConfigData(string $fileName): array
    {
        return $this->data[$fileName] ??= $this->parseFile(
            $this->fileManager->fileExists($filePath = $this->folder . DIRECTORY_SEPARATOR . ($fileName .= $this->endsWith($fileName, '.php') ? '' : '.php'))
                ? $filePath
                : throw new ConfigException("Configuration file '$fileName' not found.")
        );
    }

    private function parseFile(string $filePath): array
    {
        try {
            return $this->typeChecker->isArray($parsedData = include $filePath)
                ? $parsedData
                : throw new ConfigException("Invalid configuration format in '$filePath'. Expected an array.");
        } catch (Throwable $e) {
            throw new ConfigException("Failed to parse configuration file '$filePath'.", 0, $e);
        }
    }

    public function getAllSettings(string $fileName): array
    {
        return $this->getConfigData($fileName);
    }

    public function getSetting(string $fileName, string $key)
    {
        return $this->arrayHelper->get($this->getConfigData($fileName), $key)
            ?? throw new ConfigException("Key '$key' not found in file '$fileName'.");
    }

    public function getAllConfigs(): array
    {
        return !empty($this->data)
            ? $this->data
            : array_reduce(
                $this->files,
                fn($carry, $file) => array_merge($carry, [
                    $this->fileManager->getBaseName($file, '.php') => $this->getConfigData($this->fileManager->getBaseName($file, '.php'))
                ]),
                []
            );
    }
}
