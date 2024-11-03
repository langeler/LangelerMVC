<?php

namespace App\Core;

use App\Utilities\Finders\FileFinder;
use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Managers\FileManager;
use App\Helpers\ExistenceChecker;
use App\Helpers\ArrayHelper;
use App\Helpers\TypeChecker;
use App\Utilities\Sanitation\GeneralSanitizer;
use App\Utilities\Validation\GeneralValidator;
use App\Utilities\Validation\MediaValidator;
use App\Utilities\Sanitation\MediaSanitizer;
use App\Exceptions\ConfigException;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\EncodingTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\ConversionTrait;
use Throwable;

class Config
{
    use ManipulationTrait, EncodingTrait, CheckerTrait, ConversionTrait;

    private string $cacheKey = 'config_data';
    private bool $cacheEnabled;
    private array $envVariables = [];

    public function __construct(
        private FileFinder $files,
        private DirectoryFinder $dirs,
        private FileManager $fileManager,
        private ExistenceChecker $exists,
        private ArrayHelper $arrays,
        private TypeChecker $types,
        private GeneralSanitizer $sanitize,
        private MediaSanitizer $mediaSanitize,
        private GeneralValidator $validator,
        private MediaValidator $mediaValidator
    ) {
        $this->load();
    }

    public function load(): array
    {
        return $this->wrapInTry(fn() => $this->loadConfigFiles());
    }

    private function loadConfigFiles(): array
    {
        return $this->wrapInTry(function () {
            $envPath = $this->findEnvFilePath();
            $this->loadEnvFile($envPath);
            return $this->createOrUpdateConfigFiles($this->findConfigDir());
        });
    }

    private function findEnvFilePath(): string
    {
        $envFiles = $this->files->find(['extension' => 'env']);
        return $this->types->isArray($envFiles) && !$this->types->isEmpty($envFiles) && $this->types->isFile($envFiles[0])
            ? $envFiles[0]
            : throw new ConfigException(".env file not found.");
    }

    private function createOrUpdateConfigFiles(string $configDir): array
    {
        return $this->wrapInTry(function () use ($configDir) {
            $envGroups = $this->groupEnvByPrefix();
            $configData = [];

            foreach ($envGroups as $file => $data) {
                $configData[$file] = $this->createOrUpdateConfigFile($configDir, $file, $data);
            }

            return $configData;
        });
    }

    private function createOrUpdateConfigFile(string $configDir, string $file, array $data): array
    {
        return $this->wrapInTry(function () use ($configDir, $file, $data) {
            $filePath = $this->validateFilePath($configDir, $file);

            if (!$this->fileManager->fileExists($filePath)) {
                $this->fileManager->writeContents($filePath, "<?php\n\nreturn [];\n");
            }

            $existingData = $this->fileManager->readContents($filePath) ? include $filePath : [];
            $existingData = $this->types->isArray($existingData) ? $existingData : [];
            $mergedData = $this->arrays->merge($existingData, $data);

            $this->fileManager->writeContents(
                $filePath,
                "<?php\n\nreturn " . var_export($mergedData, true) . ";\n"
            );

            return $mergedData;
        });
    }

    private function validateFilePath(string $dir, string $file): string
    {
        $filePath = $this->mediaSanitize->sanitizeFilePathUnix("$dir/$file.php");

        return $this->mediaValidator->validateFilePathUnix($filePath)
            ? $filePath
            : throw new ConfigException("Invalid file path: $filePath");
    }

    private function groupEnvByPrefix(): array
    {
        return array_reduce(array_keys($this->envVariables), function ($carry, $key) {
            [$prefix, $suffix] = $this->split('_', $key);
            $carry[$this->toLower($prefix)][$suffix] = $this->envVariables[$key];
            return $carry;
        }, []);
    }

    private function findConfigDir(): string
    {
        return $this->wrapInTry(function () {
            $dirs = $this->dirs->find(['name' => 'Config']);
            return $this->types->isArray($dirs) && !$this->types->isEmpty($dirs) && $this->types->isDirectory($dirs[0])
                ? $dirs[0]
                : throw new ConfigException("Config directory not found.");
        });
    }

    private function loadEnvFile(string $envFilePath): void
    {
        $content = $this->fileManager->readContents($envFilePath);
        if ($content !== null) {
            $this->parseEnvFile(explode("\n", $content));
        }
    }

    private function parseEnvFile(array $envContent): void
    {
        array_walk($envContent, fn($line) => $this->processEnvLine($line));
    }

    private function processEnvLine(string $line): void
    {
        $this->wrapInTry(function () use ($line) {
            if ($this->isValidEnvLine($line)) {
                $lineParts = $this->split('=', $this->trim($line), 2);

                if ($this->types->isArray($lineParts) && count($lineParts) === 2 && !$this->isEmpty($lineParts[0])) {
                    $key = $this->sanitize->sanitizeString($this->trim($lineParts[0]));
                    $value = isset($lineParts[1]) ? $this->removeInlineComments($this->sanitize->sanitizeString($this->trim($lineParts[1]))) : '';

                    if (!empty($key)) {
                        $this->envVariables[$key] = $value;
                    }
                }
            }
        });
    }

    private function removeInlineComments(string $value): string
    {
        return preg_replace('/\s+#.*$/', '', $value);
    }

    private function isValidEnvLine(string $line): bool
    {
        $trimmedLine = $this->trim($line);
        return $this->length($trimmedLine) > 0 && $trimmedLine[0] !== '#';
    }

    private function wrapInTry(callable $callback)
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            throw new ConfigException($e->getMessage(), 0, $e);
        }
    }
}
