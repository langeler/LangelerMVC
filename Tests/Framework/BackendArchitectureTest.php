<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Core\Session;
use App\Providers\CoreProvider;
use App\Utilities\Finders\FileFinder;
use App\Utilities\Managers\IteratorManager;
use App\Utilities\Traits\ApplicationPathTrait;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class BackendArchitectureTest extends TestCase
{
    public function testApplicationPathTraitResolvesFrameworkBaseAndStoragePaths(): void
    {
        $paths = new class {
            use ApplicationPathTrait;

            public function base(): string
            {
                return $this->frameworkBasePath();
            }

            public function storage(string $path = ''): string
            {
                return $this->frameworkStoragePath($path);
            }
        };

        $projectRoot = realpath(dirname(__DIR__, 2));

        self::assertIsString($projectRoot);
        self::assertSame($projectRoot, $paths->base());
        self::assertSame($projectRoot . '/Storage', $paths->storage());
        self::assertSame($projectRoot . '/Storage/Uploads', $paths->storage('Uploads'));
    }

    public function testFinderPrefersFrameworkBasePathAsItsRoot(): void
    {
        $finder = new class(new IteratorManager()) extends FileFinder {
            public function rootPath(): ?string
            {
                return $this->root;
            }
        };

        self::assertSame(realpath(dirname(__DIR__, 2)), $finder->rootPath());
    }

    public function testSessionResolvesRelativeSavePathAgainstFrameworkBasePath(): void
    {
        $provider = new CoreProvider();
        $provider->registerServices();

        $session = $provider->getCoreService('session');

        self::assertInstanceOf(Session::class, $session);

        $method = new ReflectionMethod($session, 'resolveSavePath');
        $method->setAccessible(true);
        $resolved = $method->invoke($session);

        self::assertSame(
            realpath(dirname(__DIR__, 2) . '/Storage/Sessions'),
            $resolved
        );
    }
}
