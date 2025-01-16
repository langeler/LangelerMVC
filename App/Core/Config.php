<?php

namespace App\Core;

use App\Utilities\Finders\{
    FileFinder,
    DirectoryFinder
};
use App\Utilities\Managers\{
    FileManager,
    System\ErrorManager
};
use App\Utilities\Sanitation\{
    GeneralSanitizer,
    PatternSanitizer
};
use App\Utilities\Validation\{
    GeneralValidator,
    PatternValidator
};
use App\Utilities\Traits\{
    ManipulationTrait,    // for split(), trim(), length(), etc.
    CheckerTrait,         // for contains(), etc.
    TypeCheckerTrait,     // isString(), isEmpty()...
    ArrayTrait,           // reduce(), map(), walk(), merge() etc.
    ErrorTrait,           // wrapInTry(..., alias), no local try-catch
    ExistenceCheckerTrait // if needed for classExists, interfaceExists...
};

/**
 * Class Config
 *
 *  - Loads/merges .env environment variables and config files from a "Config" directory.
 *  - Sanitizes & optionally validates paths/lines with General/Pattern Sanitizers & (optionally) PatternValidator.
 *  - Logs + resolves exceptions via ErrorManager + ErrorTrait (alias 'config').
 *  - No loops or temp vars, using trait-based methods for iteration (reduce, map, walk).
 */
class Config
{
    use ManipulationTrait,
        CheckerTrait,
        TypeCheckerTrait,
        ArrayTrait,
        ErrorTrait,
        ExistenceCheckerTrait;

    /**
     * Cache key, environment variables, etc.
     */
    private string $cacheKey = 'config_data';
    private bool $cacheEnabled = false;
    private array $envVariables = [];

    public function __construct(
        private FileFinder $files,
        private DirectoryFinder $dirs,
        private FileManager $fileManager,
        private GeneralSanitizer $generalSanitizer,
        private PatternSanitizer $patternSanitizer,
        private GeneralValidator $generalValidator,
        private PatternValidator $patternValidator,
        private ErrorManager $errorManager
    ) {
        // On construct, we try to load config, alias 'config' for any errors.
        $this->wrapInTry(
            fn() => $this->load(),
            'config'
        );
    }

    /**
     * Public method to load the config & .env, returning merged data.
     */
    public function load(): array
    {
        return $this->wrapInTry(
            fn() => $this->loadConfigFiles(),
            'config'
        );
    }

    /**
     * Loads .env => merges with config files => final array.
     */
    private function loadConfigFiles(): array
    {
        return $this->wrapInTry(
            fn() => [
                $this->loadEnvFile($this->findEnvFilePath()),
                // Final array from createOrUpdateConfigFiles
                $this->createOrUpdateConfigFiles($this->findConfigDir())
            ][1],
            'config'
        );
    }

    /**
     * Finds a .env file or logs + throws 'config' alias if not found.
     */
    private function findEnvFilePath(): string
    {
        return $this->wrapInTry(
            fn() => $this->files->find(['extension' => 'env']) |> (
                $this->isArray($_) && !$this->isEmpty($_) && $this->fileManager->fileExists($_[0])
                    ? $_[0]
                    : (
                        $this->errorManager->logErrorMessage(
                            ".env file not found.",
                            __FILE__,
                            __LINE__,
                            'userError',
                            'config'
                        ),
                        throw $this->errorManager->resolveException(
                            'config',
                            ".env file not found."
                        )
                    )
            ),
            'config'
        );
    }

    /**
     * Creates or updates config files from env variables in the "Config" directory => final array.
     */
    private function createOrUpdateConfigFiles(string $configDir): array
    {
        return $this->wrapInTry(
            fn() => $this->map(
                $this->groupEnvByPrefix(),
                fn($data, $file) => $this->createOrUpdateConfigFile($configDir, $file, $data)
            ),
            'config'
        );
    }

    /**
     * Creates or updates one config file => merges existing data w/ new env data => writes to disk.
     */
    private function createOrUpdateConfigFile(string $configDir, string $file, array $data): array
    {
        return $this->wrapInTry(
            fn() => $this->validateFilePath($configDir, $file) |> (
                !$this->fileManager->fileExists($_)
                    && $this->fileManager->writeContents($_, "<?php\n\nreturn [];\n"),
                $existingData = $this->fileManager->readContents($_) ? include $_ : [],
                $this->fileManager->writeContents(
                    $_,
                    "<?php\n\nreturn " . var_export(
                        $this->merge($this->isArray($existingData) ? $existingData : [], $data),
                        true
                    ) . ";\n"
                )
            ),
            'config'
        );
    }

    /**
     * Validates a file path using PatternSanitizer => pathUnix, then optionally PatternValidator => pathUnix.
     */
    private function validateFilePath(string $dir, string $file): string
    {
        return $this->wrapInTry(
            fn() => $this->patternSanitizer->clean(
                // Remove invalid chars from a Unix path
                ['p' => ['pathUnix']],
                ['p' => "{$dir}/{$file}.php"]
            )['p'] |> (
                // Optionally validate the path
                $this->patternValidator->verify(
                    ['path' => ['pathUnix']],
                    ['path' => $_]
                )['path'] |> (
                    $this->fileManager->fileExists($_) || $this->fileManager->isWritable($_)
                        ? $_
                        : (
                            $this->errorManager->logErrorMessage(
                                "File path not writable or not found: $_",
                                __FILE__,
                                __LINE__,
                                'userError',
                                'config'
                            ),
                            throw $this->errorManager->resolveException(
                                'config',
                                "File path not writable or not found: $_"
                            )
                        )
                )
            ),
            'config'
        );
    }

    /**
     * Groups environment variables by prefix. e.g. "APP_DEBUG" => group 'app'=>['debug'=>value].
     */
    private function groupEnvByPrefix(): array
    {
        return $this->reduce(
            $this->getKeys($this->envVariables),
            fn($carry, $key) => (
                $prefix = $this->toLower($this->split('_', $key)[0] ?? ''),
                $suffix = $this->split('_', $key)[1] ?? '',
                $carry[$prefix][$suffix] = $this->envVariables[$key],
                $carry
            ),
            []
        );
    }

    /**
     * Finds 'Config' directory or logs + throws if not found.
     */
    private function findConfigDir(): string
    {
        return $this->wrapInTry(
            fn() => $this->dirs->find(['name' => 'Config']) |> (
                $this->isArray($_) && !$this->isEmpty($_) && $this->fileManager->fileExists($_[0])
                    ? $_[0]
                    : (
                        $this->errorManager->logErrorMessage(
                            "Config directory not found.",
                            __FILE__,
                            __LINE__,
                            'userError',
                            'config'
                        ),
                        throw $this->errorManager->resolveException(
                            'config',
                            "Config directory not found."
                        )
                    )
            ),
            'config'
        );
    }

    /**
     * Loads environment variables from the .env file.
     */
    private function loadEnvFile(string $envFilePath): void
    {
        $this->wrapInTry(
            fn() => $this->fileManager->readContents($envFilePath)
                ? $this->parseEnvFile($this->split("\n", $this->fileManager->readContents($envFilePath)))
                : null,
            'config'
        );
    }

    /**
     * Splits .env content => processes each line => stored in $this->envVariables.
     */
    private function parseEnvFile(array $envContent): void
    {
        $this->walk(
            $envContent,
            fn($line) => $this->processEnvLine($line)
        );
    }

    /**
     * If line is valid => sanitize as string => remove inline comments => store in $this->envVariables.
     */
    private function processEnvLine(string $line): void
    {
        $this->isValidEnvLine($line) && (
            $pair = $this->split('=', $line, 2),
            $key = $this->generalSanitizer->clean(
                ['envKey' => ['string', ['stripLow','stripHigh']]],
                ['envKey' => $this->trim($pair[0] ?? '')]
            )['envKey'],
            $val = $this->removeInlineComments(
                $this->generalSanitizer->clean(
                    ['envVal' => ['string', ['stripLow','stripHigh']]],
                    ['envVal' => $this->trim($pair[1] ?? '')]
                )['envVal']
            ),
            $this->envVariables[$key] = $val
        );
    }

    /**
     * Removes inline comments (# ...).
     */
    private function removeInlineComments(string $value): string
    {
        return $this->replace('/\s+#.*$/', '', $value);
    }

    /**
     * Checks if a .env line is valid => not empty, not starting with '#'.
     */
    private function isValidEnvLine(string $line): bool
    {
        return $this->length($this->trim($line)) > 0 && $line[0] !== '#';
    }
}