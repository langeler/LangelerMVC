<?php

declare(strict_types=1);

namespace App\Utilities\Traits;

/**
 * Resolves framework base and storage paths in a reusable way.
 *
 * Allows backend classes to prefer bootstrap-defined path constants while still
 * remaining usable in tests or isolated instantiation where the bootstrap has
 * not yet been executed.
 */
trait ApplicationPathTrait
{
    protected function frameworkBasePath(): string
    {
        if (defined('BASE_PATH') && is_string(BASE_PATH) && BASE_PATH !== '') {
            return BASE_PATH;
        }

        $fallback = realpath(dirname(__DIR__, 3));

        return $fallback !== false ? $fallback : dirname(__DIR__, 3);
    }

    protected function frameworkStoragePath(string $path = ''): string
    {
        $base = $this->frameworkBasePath() . DIRECTORY_SEPARATOR . 'Storage';

        if ($path === '') {
            return $base;
        }

        return $base . DIRECTORY_SEPARATOR . ltrim($path, '\\/');
    }
}
