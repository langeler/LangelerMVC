<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Contracts\Support\ArchitectureAlignmentManagerInterface;
use App\Providers\CoreProvider;
use App\Utilities\Managers\FileManager;
use App\Utilities\Managers\Support\ArchitectureAlignmentManager;
use PHPUnit\Framework\TestCase;

final class ArchitectureAlignmentManagerTest extends TestCase
{
    public function testArchitectureAlignmentManagerReportsCompleteRepositoryConventions(): void
    {
        $provider = new CoreProvider();
        $provider->registerServices();
        $manager = $provider->getCoreService('architecture');

        self::assertInstanceOf(ArchitectureAlignmentManagerInterface::class, $manager);

        $payload = $manager->inspect();

        self::assertTrue((bool) $payload['ok']);
        self::assertSame([], $payload['errors']);
        self::assertSame([], $payload['warnings']);
        self::assertTrue((bool) $payload['checks']['repository_contract']['ok']);
        self::assertTrue((bool) $payload['checks']['app_layer_boundaries']['ok']);
        self::assertTrue((bool) $payload['checks']['class_placement']['ok']);
        self::assertTrue((bool) $payload['checks']['public_bootstrap']['ok']);
        self::assertTrue((bool) $payload['checks']['config_data_release']['ok']);
        self::assertTrue((bool) $payload['checks']['tests_ci_scripts']['ok']);
        self::assertTrue((bool) $payload['checks']['strict_types']['ok']);
        self::assertTrue((bool) $payload['checks']['manager_placement']['ok']);
        self::assertTrue((bool) $payload['checks']['module_contracts']['ok']);
        self::assertTrue((bool) $payload['checks']['presentation_native_surface']['ok']);
        self::assertTrue((bool) $payload['checks']['documentation_alignment']['ok']);
        self::assertArrayHasKey('repository_contract', $payload['rules']);
        self::assertArrayHasKey('class_placement', $payload['rules']);
        self::assertArrayHasKey('manager_placement', $payload['rules']);
        self::assertGreaterThan(400, $payload['checks']['class_placement']['class_count']);
        self::assertContains(
            'App/Support/Commerce/ShippingManager.php',
            $payload['checks']['class_placement']['compatibility_aliases']
        );
    }

    public function testArchitectureAlignmentManagerReportsMissingRepositoryShape(): void
    {
        $manager = new ArchitectureAlignmentManager(
            new FileManager(),
            sys_get_temp_dir() . '/langelermvc-architecture-missing-root-' . bin2hex(random_bytes(4))
        );

        $payload = $manager->inspect();
        $violations = $manager->violations();

        self::assertFalse((bool) $payload['ok']);
        self::assertNotSame([], $payload['errors']);
        self::assertNotSame([], $violations['errors']);
        self::assertArrayHasKey('repository_contract', $payload['checks']);
        self::assertFalse((bool) $payload['checks']['repository_contract']['ok']);
        self::assertArrayHasKey('module_contracts', $payload['checks']);
        self::assertFalse((bool) $payload['checks']['module_contracts']['ok']);
    }
}
