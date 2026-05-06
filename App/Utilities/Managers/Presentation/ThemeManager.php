<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Presentation;

use App\Core\Config;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Traits\TypeCheckerTrait;

class ThemeManager
{
    use ArrayTrait;
    use ManipulationTrait;
    use PatternTrait;
    use TypeCheckerTrait;

    /**
     * @var list<string>
     */
    private const VALID_MODES = ['light', 'dark', 'system'];

    public function __construct(private readonly Config $config)
    {
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function catalog(): array
    {
        $themes = $this->config->get('theme', 'THEMES', []);

        if (!$this->isArray($themes) || $themes === []) {
            return $this->fallbackCatalog();
        }

        $catalog = [];

        foreach ($themes as $name => $theme) {
            if (!$this->isArray($theme)) {
                continue;
            }

            $normalizedName = $this->normalizeName((string) $name);

            if ($normalizedName === '') {
                continue;
            }

            $catalog[$normalizedName] = [
                'name' => $normalizedName,
                'label' => (string) ($theme['LABEL'] ?? $theme['label'] ?? $this->capitalizeWords($this->replaceText('-', ' ', $normalizedName))),
                'mode' => $this->normalizeMode((string) ($theme['MODE'] ?? $theme['mode'] ?? $this->defaultMode())),
                'description' => (string) ($theme['DESCRIPTION'] ?? $theme['description'] ?? ''),
                'bootstrap' => (string) ($theme['BOOTSTRAP'] ?? $theme['bootstrap'] ?? '5.3 LTS-compatible'),
                'surface' => $this->isArray($theme['SURFACE'] ?? null) ? $theme['SURFACE'] : [],
            ];
        }

        return $catalog !== [] ? $catalog : $this->fallbackCatalog();
    }

    /**
     * @return array<string, mixed>
     */
    public function activeTheme(?string $requested = null): array
    {
        $catalog = $this->catalog();
        $default = $this->normalizeName((string) $this->config->get('theme', 'DEFAULT', 'bootstrap-light'));
        $requested = $this->normalizeName((string) ($requested ?? ''));
        $name = $requested !== '' && isset($catalog[$requested])
            ? $requested
            : (isset($catalog[$default]) ? $default : $this->keyFirst($catalog));
        $theme = $catalog[(string) $name] ?? reset($catalog);
        $mode = $this->defaultMode();

        return [
            ...$theme,
            'name' => (string) ($theme['name'] ?? $name),
            'mode' => $mode,
            'theme_mode' => $this->normalizeMode((string) ($theme['mode'] ?? $mode)),
            'default_mode' => $this->defaultMode(),
            'asset_css' => $this->asset('CSS', '/assets/css/langelermvc-theme.css'),
            'asset_js' => $this->asset('JS', '/assets/js/langelermvc-theme.js'),
            'allow_user_selection' => $this->booleanConfig('ALLOW_USER_SELECTION', true),
            'storage_key' => (string) $this->config->get('theme', 'STORAGE_KEY', 'langelermvc.theme'),
            'cookie' => (string) $this->config->get('theme', 'COOKIE', 'langelermvc_theme'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function layoutGlobals(string $surface = 'public', ?string $requested = null): array
    {
        $theme = $this->activeTheme($requested);
        $surface = $this->normalizeName($surface) ?: 'public';

        return [
            'themeName' => $theme['name'],
            'themeLabel' => $theme['label'],
            'themeMode' => $theme['mode'],
            'themePreferredMode' => $theme['theme_mode'],
            'themeDefaultMode' => $theme['default_mode'],
            'themeSurface' => $surface,
            'themeClass' => 'theme-' . $theme['name'],
            'themeAssetCss' => $theme['asset_css'],
            'themeAssetJs' => $theme['asset_js'],
            'themeToggleEnabled' => (bool) $theme['allow_user_selection'],
            'themeStorageKey' => $theme['storage_key'],
            'themeCookie' => $theme['cookie'],
            'themeCatalog' => $this->catalog(),
        ];
    }

    private function defaultMode(): string
    {
        return $this->normalizeMode((string) $this->config->get('theme', 'MODE', 'system'));
    }

    private function normalizeMode(string $mode): string
    {
        $mode = $this->normalizeName($mode);

        return $this->any(self::VALID_MODES, static fn(string $valid): bool => $valid === $mode) ? $mode : 'system';
    }

    private function normalizeName(string $value): string
    {
        $value = $this->toLower($this->trimString($value));
        $value = $this->replaceByPattern('/[^a-z0-9_-]+/', '-', $value) ?? '';

        return $this->trimString($value, '-_');
    }

    private function asset(string $key, string $fallback): string
    {
        $asset = $this->config->get('theme', 'ASSETS.' . $key, $fallback);
        $asset = $this->isScalar($asset) ? $this->trimString((string) $asset) : '';

        return $asset !== '' ? $asset : $fallback;
    }

    private function booleanConfig(string $key, bool $fallback): bool
    {
        $value = $this->config->get('theme', $key, $fallback);

        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $fallback;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function fallbackCatalog(): array
    {
        return [
            'bootstrap-light' => [
                'name' => 'bootstrap-light',
                'label' => 'Bootstrap Light',
                'mode' => 'light',
                'description' => 'Professional light theme using Bootstrap-compatible tokens.',
                'bootstrap' => '5.3 LTS-compatible',
                'surface' => [],
            ],
            'bootstrap-dark' => [
                'name' => 'bootstrap-dark',
                'label' => 'Bootstrap Dark',
                'mode' => 'dark',
                'description' => 'Professional dark theme using Bootstrap-compatible tokens.',
                'bootstrap' => '5.3 LTS-compatible',
                'surface' => [],
            ],
        ];
    }
}
