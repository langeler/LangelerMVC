<?php

namespace App\Utilities\Managers;

use App\Utilities\Finders\{
    DirectoryFinder,
    FileFinder
};
use App\Utilities\Traits\{
    ArrayTrait,
    TypeCheckerTrait,
    CheckerTrait,
    ErrorTrait   // new: replaces local wrapInTry
};
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Sanitation\{
    PatternSanitizer
};
use App\Utilities\Validation\{
    PatternValidator
};

/**
 * Class SettingsManager
 *
 * Manages application configuration files and contents,
 * with validation, sanitization, and retrieval of settings.
 * Replaces local wrapInTry(...) with ErrorTrait + ErrorManager->resolveException('settings', ...).
 */
class SettingsManager
{
    use ArrayTrait,
        TypeCheckerTrait,
        CheckerTrait,
        ErrorTrait; // Provides wrapInTry(..., 'settings'), etc.

    /**
     * The validated folder path for the config directory.
     */
    private string $folder;

    /**
     * List of .php config files in the folder.
     */
    private array $files = [];

    /**
     * Parsed config data, keyed by file name => array data.
     */
    private array $data = [];

    public function __construct(
        private readonly DirectoryFinder $dirFinder,
        private readonly FileFinder $fileFinder,
        private readonly FileManager $fileManager,
        private readonly PatternSanitizer $patternSanitizer,   // For path sanitization
        private readonly PatternValidator $patternValidator,    // If we want to validate 'pathUnix'
        private readonly ErrorManager $errorManager
    ) {
        // We locate & validate the folder, retrieve files, all under alias 'settings'.
        $this->folder = $this->wrapInTry(
            fn() => $this->validateFolderPath($this->locateConfigFolder()),
            'settings'
        );

        $this->files = $this->wrapInTry(
            fn() => $this->retrieveConfigFiles(),
            'settings'
        );
    }

    /**
     * Locates the "Config" folder or logs & throws if not found.
     */
    private function locateConfigFolder(): string
    {
        return $this->wrapInTry(
            fn() => $this->dirFinder->find(['name' => 'Config']) |> (
                $this->isArray($_) && !$this->isEmpty($_) && $this->fileManager->isDirectory($_[0])
                    ? $_[0]
                    : (
                        $this->errorManager->logErrorMessage(
                            "Configuration directory not found.",
                            __FILE__,
                            __LINE__,
                            'userError',
                            'settings'
                        ),
                        throw $this->errorManager->resolveException(
                            'settings',
                            "Configuration directory not found."
                        )
                    )
            ),
            'settings'
        );
    }

    /**
     * Sanitizes & optionally validates the folder path (Unix).
     */
    private function validateFolderPath(string $folderPath): string
    {
        return $this->wrapInTry(
            fn() => $this->patternSanitizer->clean(
                ['p' => ['pathUnix']],
                ['p' => $folderPath]
            )['p'] |> (
                // Optionally validate with patternValidator => 'pathUnix'
                $this->patternValidator->verify(
                    ['path' => ['pathUnix']],
                    ['path' => $_]
                )['path'] |> (
                    $this->isValidFilePath($_) ? $_ : (
                        $this->errorManager->logErrorMessage(
                            "Invalid configuration directory path: $_",
                            __FILE__,
                            __LINE__,
                            'userError',
                            'settings'
                        ),
                        throw $this->errorManager->resolveException(
                            'settings',
                            "Invalid configuration directory path: $_"
                        )
                    )
                )
            ),
            'settings'
        );
    }

    /**
     * Retrieves all .php config files in the validated folder.
     */
    private function retrieveConfigFiles(): array
    {
        return $this->wrapInTry(
            fn() => $this->fileFinder->find(['extension' => 'php'], $this->folder) |> (
                $this->isArray($_) && !$this->isEmpty($_)
                    ? $_
                    : []
            ),
            'settings'
        );
    }

    /**
     * Parses an individual .php config file => returns array data or throws if invalid.
     */
    private function parseFile(string $filePath): array
    {
        return $this->wrapInTry(
            fn() => $this->isArray($parsed = @include $filePath)
                ? $parsed
                : (
                    $this->errorManager->logErrorMessage(
                        "Invalid config format in '$filePath'. Expected an array.",
                        __FILE__,
                        __LINE__,
                        'userError',
                        'settings'
                    ),
                    throw $this->errorManager->resolveException(
                        'settings',
                        "Invalid config format in '$filePath'."
                    )
                ),
            'settings'
        );
    }

    /**
     * Public method: retrieves ALL settings from a specified config file.
     */
    public function getAllSettings(string $fileName): array
    {
        return $this->wrapInTry(
            fn() => $this->getConfigData($fileName),
            'settings'
        );
    }

    /**
     * Public method: retrieves a single setting by key from a config file.
     */
    public function getSetting(string $fileName, string $key): mixed
    {
        return $this->wrapInTry(
            fn() => $this->get($this->getConfigData($fileName), $key)
                ?? (
                    $this->errorManager->logErrorMessage(
                        "Key '$key' not found in file '$fileName'.",
                        __FILE__,
                        __LINE__,
                        'userError',
                        'settings'
                    ),
                    throw $this->errorManager->resolveException(
                        'settings',
                        "Key '$key' not found in file '$fileName'."
                    )
                ),
            'settings'
        );
    }

    /**
     * Returns all parsed config data from every .php file in the folder.
     */
    public function getAllConfigs(): array
    {
        return $this->wrapInTry(
            fn() => !$this->isEmpty($this->data)
                ? $this->data
                : $this->reduce(
                    $this->files,
                    fn($carry, $file) => $this->merge(
                        $carry,
                        [
                            $this->fileManager->getBaseName($file, '.php') =>
                                $this->getConfigData($this->fileManager->getBaseName($file, '.php'))
                        ]
                    ),
                    []
                ),
            'settings'
        );
    }

    /**
     * Retrieves and caches config data for a given file name.
     */
    private function getConfigData(string $fileName): array
    {
        return $this->wrapInTry(
            fn() => $this->data[$fileName] ??= (
                $filePath = $this->buildFilePath($fileName),
                $this->fileManager->fileExists($filePath)
                    ? $this->parseFile($filePath)
                    : (
                        $this->errorManager->logErrorMessage(
                            "Configuration file '$fileName' not found.",
                            __FILE__,
                            __LINE__,
                            'userError',
                            'settings'
                        ),
                        throw $this->errorManager->resolveException(
                            'settings',
                            "Configuration file '$fileName' not found."
                        )
                    )
            ),
            'settings'
        );
    }

    /**
     * Builds the full path to a config file, sanitizing + optionally validating it if needed.
     */
    private function buildFilePath(string $fileName): string
    {
        return $this->wrapInTry(
            fn() => $this->patternSanitizer->clean(
                ['p' => ['pathUnix']],
                ['p' => $this->folder . '/' .
                    ($this->endsWith($fileName, '.php') ? $fileName : "$fileName.php")]
            )['p'],
            'settings'
        );
    }

    /**
     * Checks if a file path is valid (readable directory or file).
     */
    private function isValidFilePath(string $filePath): bool
    {
        return $this->fileManager->isReadable($filePath)
            && (
                $this->fileManager->isDirectory($filePath)
                || $this->fileManager->fileExists($filePath)
            );
    }
}