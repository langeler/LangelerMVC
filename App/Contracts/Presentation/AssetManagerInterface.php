<?php

declare(strict_types=1);

namespace App\Contracts\Presentation;

interface AssetManagerInterface
{
    public function sourcePath(string $type, string $asset): string;

    public function publicPath(string $type, string $asset): string;

    public function publicUrl(string $type, string $asset): string;

    public function versionedUrl(string $type, string $asset): string;

    /**
     * @param array<string, mixed> $attributes
     */
    public function tag(string $type, string $asset, array $attributes = []): string;

    /**
     * @param array<string, mixed> $attributes
     */
    public function versionedTag(string $type, string $asset, array $attributes = []): string;

    /**
     * @param array<string, mixed> $attributes
     */
    public function preloadTag(string $type, string $asset, array $attributes = []): string;

    /**
     * @param array<string, mixed> $bundle
     */
    public function registerBundle(string $name, array $bundle): static;

    /**
     * @return array<string, mixed>
     */
    public function bundle(string $name): array;

    /**
     * @param array<string, mixed> $attributes
     */
    public function bundleTags(string $name, array $attributes = []): string;

    /**
     * @param array<string, mixed> $attributes
     */
    public function attributes(array $attributes): string;

    /**
     * @return array<string, mixed>
     */
    public function synchronizationReport(): array;
}
