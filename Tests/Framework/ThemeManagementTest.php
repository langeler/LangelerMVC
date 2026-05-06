<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Contracts\Presentation\AssetManagerInterface;
use App\Contracts\Presentation\HtmlManagerInterface;
use App\Core\Config;
use App\Providers\CoreProvider;
use App\Utilities\Managers\Presentation\AssetManager;
use App\Utilities\Managers\Presentation\HtmlManager;
use App\Utilities\Managers\Presentation\ThemeManager;
use PHPUnit\Framework\TestCase;

final class ThemeManagementTest extends TestCase
{
    public function testThemeManagerExposesFrameworkWideLayoutGlobals(): void
    {
        $themes = $this->themeManager();
        $catalog = $themes->catalog();
        $globals = $themes->layoutGlobals('admin');

        self::assertArrayHasKey('bootstrap-light', $catalog);
        self::assertArrayHasKey('bootstrap-dark', $catalog);
        self::assertArrayHasKey('bootstrap-system', $catalog);
        self::assertSame('bootstrap-light', $globals['themeName']);
        self::assertSame('system', $globals['themeMode']);
        self::assertSame('admin', $globals['themeSurface']);
        self::assertSame('/assets/css/langelermvc-theme.css', $globals['themeAssetCss']);
        self::assertSame('/assets/js/langelermvc-theme.js', $globals['themeAssetJs']);
        self::assertTrue((bool) $globals['themeToggleEnabled']);
    }

    public function testThemeAssetsAreReleaseTrackedAndSynchronized(): void
    {
        $root = dirname(__DIR__, 2);
        $sourceCss = $root . '/App/Resources/css/langelermvc-theme.css';
        $publicCss = $root . '/Public/assets/css/langelermvc-theme.css';
        $sourceJs = $root . '/App/Resources/js/langelermvc-theme.js';
        $publicJs = $root . '/Public/assets/js/langelermvc-theme.js';

        self::assertFileExists($sourceCss);
        self::assertFileExists($publicCss);
        self::assertFileExists($sourceJs);
        self::assertFileExists($publicJs);
        self::assertSame(hash_file('sha256', $sourceCss), hash_file('sha256', $publicCss));
        self::assertSame(hash_file('sha256', $sourceJs), hash_file('sha256', $publicJs));

        $css = (string) file_get_contents($sourceCss);
        $js = (string) file_get_contents($sourceJs);

        self::assertStringContainsString('[data-theme-mode="dark"]', $css);
        self::assertStringContainsString('--bs-body-bg', $css);
        self::assertStringContainsString('.theme-toggle', $css);
        self::assertStringContainsString('data-theme-toggle', $js);
        self::assertStringContainsString('localStorage', $js);
    }

    public function testThemeManagerIsRegisteredAsCoreService(): void
    {
        $provider = new CoreProvider();
        $provider->registerServices();
        $service = $provider->getCoreService('themes');

        self::assertInstanceOf(ThemeManager::class, $service);
        self::assertInstanceOf(ThemeManager::class, $provider->resolveClass(\App\Support\Theming\ThemeManager::class));
    }

    public function testAssetManagerIsRegisteredAndOwnsPresentationAssetContract(): void
    {
        $provider = new CoreProvider();
        $provider->registerServices();
        $service = $provider->getCoreService('assets');

        self::assertInstanceOf(AssetManagerInterface::class, $service);
        self::assertInstanceOf(AssetManager::class, $service);
        self::assertSame('/assets/css/langelermvc-theme.css', $service->publicUrl('styles', 'langelermvc-theme.css'));
        self::assertMatchesRegularExpression(
            '#^/assets/css/langelermvc-theme\.css\?v=[a-f0-9]{12}$#',
            $service->versionedUrl('styles', 'langelermvc-theme.css')
        );
        self::assertStringContainsString(
            '<link rel="stylesheet" href="/assets/css/langelermvc-theme.css"',
            $service->tag('css', 'langelermvc-theme.css')
        );
        self::assertStringContainsString(
            '<link rel="preload" href="/assets/css/langelermvc-theme.css',
            $service->preloadTag('css', 'langelermvc-theme.css', ['versioned' => true])
        );
        self::assertStringContainsString(
            '<script src="/assets/js/langelermvc-theme.js" defer></script>',
            $service->tag('js', 'langelermvc-theme.js', ['defer' => true])
        );
        self::assertStringContainsString(
            'langelermvc-theme.css?v=',
            $service->bundleTags('framework-theme')
        );

        $report = $service->synchronizationReport();

        self::assertTrue((bool) $report['ok']);
    }

    public function testHtmlManagerIsRegisteredAndOwnsReusableHtmlHelpers(): void
    {
        $provider = new CoreProvider();
        $provider->registerServices();
        $service = $provider->getCoreService('html');

        self::assertInstanceOf(HtmlManagerInterface::class, $service);
        self::assertInstanceOf(HtmlManager::class, $service);
        self::assertSame('alpha beta', $service->classList(['alpha' => true, 'hidden' => false, 'beta']));
        self::assertSame(' data-controller="panel" disabled', $service->attributes(['data_controller' => 'panel', 'disabled' => true]));
        self::assertSame('<input type="hidden" name="_method" value="PATCH">', $service->methodField('patch'));
        self::assertSame('{"tag":"\\u003Cscript\\u003E"}', $service->json(['tag' => '<script>']));
    }

    private function themeManager(): ThemeManager
    {
        $provider = new CoreProvider();
        $provider->registerServices();
        $config = $provider->getCoreService('config');

        self::assertInstanceOf(Config::class, $config);

        return new ThemeManager($config);
    }
}
