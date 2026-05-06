<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Contracts\Support\FrameworkLayerManagerInterface;
use App\Providers\CoreProvider;
use App\Utilities\Managers\FileManager;
use App\Utilities\Managers\Support\FrameworkLayerManager;
use PHPUnit\Framework\TestCase;

final class FrameworkLayerManagerTest extends TestCase
{
    public function testFrameworkLayerManagerReportsCompleteRepositoryLayers(): void
    {
        $provider = new CoreProvider();
        $provider->registerServices();
        $manager = $provider->getCoreService('frameworkLayers');

        self::assertInstanceOf(FrameworkLayerManagerInterface::class, $manager);

        $payload = $manager->inspect();

        self::assertTrue((bool) $payload['ok']);
        self::assertSame([], $payload['errors']);
        self::assertSame([], $payload['missing_required_paths']);
        self::assertGreaterThanOrEqual(14, (int) $payload['layer_count']);
        self::assertArrayHasKey('presentation', $payload['layers']);
        self::assertArrayHasKey('modules', $payload['layers']);
        self::assertArrayHasKey('release_docs_data', $payload['layers']);
        self::assertTrue((bool) $payload['layers']['presentation']['ok']);
        self::assertContains('App/Templates/Layouts', $payload['layers']['presentation']['required_paths']);
    }

    public function testFrameworkLayerManagerReportsMissingRequiredPathsByLayer(): void
    {
        $manager = new FrameworkLayerManager(
            new FileManager(),
            sys_get_temp_dir() . '/langelermvc-missing-layer-root-' . bin2hex(random_bytes(4))
        );

        $missing = $manager->missingRequiredPaths();
        $payload = $manager->inspect();

        self::assertArrayHasKey('public_bootstrap', $missing);
        self::assertContains('Public/index.php', $missing['public_bootstrap']);
        self::assertFalse((bool) $payload['ok']);
        self::assertNotSame([], $payload['errors']);
    }
}
