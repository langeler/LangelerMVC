<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Presentation;

use App\Contracts\Presentation\AssetManagerInterface;
use App\Contracts\Presentation\HtmlManagerInterface;
use App\Exceptions\Presentation\ViewException;
use App\Utilities\Managers\FileManager;
use App\Utilities\Sanitation\PatternSanitizer;
use App\Utilities\Traits\ApplicationPathTrait;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\EncodingTrait;
use App\Utilities\Traits\HashingTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Traits\TypeCheckerTrait;
use App\Utilities\Validation\PatternValidator;

final class AssetManager implements AssetManagerInterface
{
    use ApplicationPathTrait;
    use ArrayTrait;
    use CheckerTrait;
    use EncodingTrait;
    use HashingTrait;
    use ManipulationTrait;
    use PatternTrait;
    use TypeCheckerTrait;

    /**
     * @var array<string, string>
     */
    private array $resolvedPaths = [];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $bundles = [
        'framework-theme' => [
            'css' => [
                ['asset' => 'langelermvc-theme.css', 'attributes' => ['versioned' => true]],
            ],
            'js' => [
                ['asset' => 'langelermvc-theme.js', 'attributes' => ['defer' => true, 'versioned' => true]],
            ],
        ],
    ];

    public function __construct(
        private readonly FileManager $files,
        private readonly PatternSanitizer $sanitizer,
        private readonly PatternValidator $validator,
        private readonly ?HtmlManagerInterface $html = null
    ) {
    }

    public function sourcePath(string $type, string $asset): string
    {
        return $this->resolveExistingFile($this->sourceBasePath(), $type, $asset, 'source asset');
    }

    public function publicPath(string $type, string $asset): string
    {
        return $this->resolveExistingFile($this->publicBasePath(), $type, $asset, 'public asset');
    }

    public function publicUrl(string $type, string $asset): string
    {
        $asset = $this->trimString($asset);

        if ($this->isAbsoluteUrl($asset) || $this->startsWith($asset, '/')) {
            return $asset;
        }

        return '/assets/' . $this->normalizeType($type) . '/' . $this->normalizeAssetName($asset);
    }

    public function versionedUrl(string $type, string $asset): string
    {
        $url = $this->publicUrl($type, $asset);

        if ($this->isAbsoluteUrl($url) || !$this->startsWith($url, '/assets/')) {
            return $url;
        }

        $assetName = $this->assetNameFromPublicUrl($url, $type);

        if ($assetName === '') {
            return $url;
        }

        try {
            $path = $this->publicPath($type, $assetName);
            $hash = $this->substring((string) $this->hashFile($path, 'sha256'), 0, 12);
        } catch (\Throwable) {
            return $url;
        }

        if ($hash === '') {
            return $url;
        }

        return $url . ($this->contains($url, '?') ? '&' : '?') . 'v=' . $hash;
    }

    public function tag(string $type, string $asset, array $attributes = []): string
    {
        $versioned = (bool) ($attributes['versioned'] ?? $attributes['version'] ?? false);
        unset($attributes['versioned'], $attributes['version']);
        $url = $versioned ? $this->versionedUrl($type, $asset) : $this->publicUrl($type, $asset);

        return match ($this->normalizeType($type)) {
            'css' => '<link rel="stylesheet" href="'
                . $this->escapeAttribute($url)
                . '"'
                . $this->attributes($attributes)
                . '>',
            'js' => '<script src="'
                . $this->escapeAttribute($url)
                . '"'
                . $this->attributes($attributes)
                . '></script>',
            'images' => '<img src="'
                . $this->escapeAttribute($url)
                . '"'
                . $this->attributes($this->replaceElements(['alt' => ''], $attributes))
                . '>',
        };
    }

    public function versionedTag(string $type, string $asset, array $attributes = []): string
    {
        return $this->tag($type, $asset, $this->replaceElements($attributes, ['versioned' => true]));
    }

    public function preloadTag(string $type, string $asset, array $attributes = []): string
    {
        $normalizedType = $this->normalizeType($type);
        $as = match ($normalizedType) {
            'css' => 'style',
            'js' => 'script',
            'images' => 'image',
        };
        $versioned = (bool) ($attributes['versioned'] ?? $attributes['version'] ?? false);
        unset($attributes['versioned'], $attributes['version']);
        $url = $versioned ? $this->versionedUrl($normalizedType, $asset) : $this->publicUrl($normalizedType, $asset);

        return '<link rel="preload" href="'
            . $this->escapeAttribute($url)
            . '"'
            . $this->attributes($this->replaceElements(['as' => $as], $attributes))
            . '>';
    }

    public function registerBundle(string $name, array $bundle): static
    {
        $normalized = $this->normalizeBundleName($name);
        $this->bundles[$normalized] = $this->normalizeBundle($bundle);

        return $this;
    }

    public function bundle(string $name): array
    {
        $normalized = $this->normalizeBundleName($name);

        if (!$this->keyExists($this->bundles, $normalized)) {
            throw new ViewException(sprintf('Asset bundle [%s] is not registered.', $name));
        }

        return $this->bundles[$normalized];
    }

    public function bundleTags(string $name, array $attributes = []): string
    {
        $bundle = $this->bundle($name);
        $tags = [];

        foreach ((array) ($bundle['preload'] ?? []) as $entry) {
            [$type, $asset, $entryAttributes] = $this->normalizeBundleEntry($entry, 'css');
            $tags[] = $this->preloadTag($type, $asset, $this->replaceElements($entryAttributes, (array) ($attributes['preload'] ?? [])));
        }

        foreach ((array) ($bundle['css'] ?? []) as $entry) {
            [$type, $asset, $entryAttributes] = $this->normalizeBundleEntry($entry, 'css');
            $tags[] = $this->tag($type, $asset, $this->replaceElements($entryAttributes, (array) ($attributes['css'] ?? [])));
        }

        foreach ((array) ($bundle['js'] ?? []) as $entry) {
            [$type, $asset, $entryAttributes] = $this->normalizeBundleEntry($entry, 'js');
            $tags[] = $this->tag($type, $asset, $this->replaceElements($entryAttributes, (array) ($attributes['js'] ?? [])));
        }

        return $this->joinStrings("\n", $tags);
    }

    public function synchronizationReport(): array
    {
        $pairs = [
            'css/langelermvc-theme.css',
            'js/langelermvc-theme.js',
        ];
        $assets = [];

        foreach ($pairs as $asset) {
            [$type, $file] = $this->splitAssetPair($asset);
            $source = $this->safePath(fn(): string => $this->sourcePath($type, $file));
            $public = $this->safePath(fn(): string => $this->publicPath($type, $file));
            $sourceHash = $source !== null ? $this->hashFile($source, 'sha256') : false;
            $publicHash = $public !== null ? $this->hashFile($public, 'sha256') : false;

            $assets[$asset] = [
                'source' => $source,
                'public' => $public,
                'source_exists' => $source !== null,
                'public_exists' => $public !== null,
                'synchronized' => $sourceHash !== false && $sourceHash === $publicHash,
            ];
        }

        return [
            'ok' => $this->all($assets, static fn(array $asset): bool => (bool) $asset['synchronized']),
            'assets' => $assets,
        ];
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function attributes(array $attributes): string
    {
        if ($this->html instanceof HtmlManagerInterface) {
            return $this->html->attributes($attributes);
        }

        $parts = [];

        foreach ($attributes as $name => $value) {
            $name = $this->normalizeAttributeName((string) $name);

            if ($name === '' || $value === false || $value === null) {
                continue;
            }

            if ($value === true) {
                $parts[] = $this->escapeAttribute($name);
                continue;
            }

            $parts[] = $this->escapeAttribute($name)
                . '="'
                . $this->escapeAttribute($this->stringify($value))
                . '"';
        }

        return $parts === [] ? '' : ' ' . $this->joinStrings(' ', $parts);
    }

    private function resolveExistingFile(string $basePath, string $type, string $asset, string $label): string
    {
        $directory = $this->resolveDirectory($basePath, $this->normalizeType($type));
        $assetName = $this->normalizeAssetName($asset);
        $path = $directory . DIRECTORY_SEPARATOR . $this->replaceText('/', DIRECTORY_SEPARATOR, $assetName);
        $cacheKey = $label . ':' . $path;

        if ($this->keyExists($this->resolvedPaths, $cacheKey)) {
            return $this->resolvedPaths[$cacheKey];
        }

        if (!$this->files->fileExists($path)) {
            throw new ViewException(sprintf('The %s [%s] does not exist.', $label, $assetName));
        }

        $this->resolvedPaths[$cacheKey] = $this->files->normalizePath($this->files->getRealPath($path) ?? $path);

        return $this->resolvedPaths[$cacheKey];
    }

    private function resolveDirectory(string $basePath, string $type): string
    {
        $path = $basePath . DIRECTORY_SEPARATOR . $type;

        if (!$this->files->isDirectory($path)) {
            throw new ViewException(sprintf('Asset directory [%s] does not exist.', $path));
        }

        return $this->files->normalizePath($this->files->getRealPath($path) ?? $path);
    }

    private function normalizeType(string $type): string
    {
        $normalized = $this->toLower($this->trimString($type));

        return match ($normalized) {
            'css', 'style', 'styles', 'stylesheet', 'stylesheets' => 'css',
            'js', 'script', 'scripts', 'javascript' => 'js',
            'image', 'images', 'img', 'media' => 'images',
            default => throw new ViewException(sprintf('Unsupported asset type [%s].', $type)),
        };
    }

    private function normalizeAssetName(string $asset): string
    {
        $asset = $this->replaceText('\\', '/', $this->trimString($asset));
        $asset = (string) $this->sanitizer->sanitizePathUnix($asset);
        $asset = $this->trimString($asset, '/');

        if ($asset === '') {
            throw new ViewException('Asset identifier cannot be empty.');
        }

        $segments = $this->splitString('/', $asset);

        if ($this->any($segments, static fn(string $segment): bool => $segment === '' || $segment === '.' || $segment === '..')) {
            throw new ViewException(sprintf('Unsafe asset identifier [%s].', $asset));
        }

        foreach ($segments as $index => $segment) {
            $isLast = $index === $this->countElements($segments) - 1;
            $valid = $isLast
                ? $this->validator->validateFileName($segment) || $this->validator->validateDirectory($segment)
                : $this->validator->validateDirectory($segment);

            if (!$valid) {
                throw new ViewException(sprintf('Invalid asset identifier [%s].', $asset));
            }
        }

        return $this->joinStrings('/', $segments);
    }

    private function normalizeAttributeName(string $name): string
    {
        $name = $this->toLower($this->trimString($name));
        $name = $this->replaceByPattern('/[^a-z0-9_:-]+/', '-', $name) ?? '';

        return $this->trimString($this->replaceText('_', '-', $name), '-');
    }

    private function normalizeBundleName(string $name): string
    {
        $normalized = $this->toLower($this->trimString($name));
        $normalized = $this->replaceByPattern('/[^a-z0-9_.:-]+/', '-', $normalized) ?? '';
        $normalized = $this->trimString($normalized, '-_.:');

        if ($normalized === '') {
            throw new ViewException('Asset bundle name cannot be empty.');
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $bundle
     * @return array<string, mixed>
     */
    private function normalizeBundle(array $bundle): array
    {
        return [
            'preload' => array_values((array) ($bundle['preload'] ?? [])),
            'css' => array_values((array) ($bundle['css'] ?? [])),
            'js' => array_values((array) ($bundle['js'] ?? [])),
        ];
    }

    /**
     * @return array{0:string,1:string,2:array<string,mixed>}
     */
    private function normalizeBundleEntry(mixed $entry, string $defaultType): array
    {
        if ($this->isString($entry)) {
            return [$defaultType, $entry, []];
        }

        if (!$this->isArray($entry)) {
            throw new ViewException('Asset bundle entries must be strings or arrays.');
        }

        $type = (string) ($entry['type'] ?? $defaultType);
        $asset = (string) ($entry['asset'] ?? $entry['file'] ?? $entry['path'] ?? '');
        $attributes = (array) ($entry['attributes'] ?? $entry['attrs'] ?? []);

        if ($asset === '') {
            throw new ViewException('Asset bundle entry is missing an asset path.');
        }

        return [$type, $asset, $attributes];
    }

    private function assetNameFromPublicUrl(string $url, string $type): string
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?: '');
        $prefix = '/assets/' . $this->normalizeType($type) . '/';

        if (!$this->startsWith($path, $prefix)) {
            return '';
        }

        return $this->trimString($this->substring($path, strlen($prefix)), '/');
    }

    private function sourceBasePath(): string
    {
        return $this->frameworkBasePath() . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Resources';
    }

    private function publicBasePath(): string
    {
        return $this->frameworkBasePath() . DIRECTORY_SEPARATOR . 'Public' . DIRECTORY_SEPARATOR . 'assets';
    }

    private function isAbsoluteUrl(string $value): bool
    {
        return $this->startsWith($value, 'http://')
            || $this->startsWith($value, 'https://')
            || $this->startsWith($value, '//')
            || $this->startsWith($value, 'data:');
    }

    private function escapeAttribute(string $value): string
    {
        return $this->encodeSpecialCharsString($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function stringify(mixed $value): string
    {
        if ($this->isBool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($this->isScalar($value)) {
            return (string) $value;
        }

        return '';
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitAssetPair(string $asset): array
    {
        $parts = $this->splitString('/', $asset, 2);

        return [(string) ($parts[0] ?? ''), (string) ($parts[1] ?? '')];
    }

    /**
     * @param callable(): string $callback
     */
    private function safePath(callable $callback): ?string
    {
        try {
            return $callback();
        } catch (\Throwable) {
            return null;
        }
    }
}
