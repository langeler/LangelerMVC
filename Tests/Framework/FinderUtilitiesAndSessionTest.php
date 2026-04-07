<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Core\Config;
use App\Core\Session;
use App\Providers\CoreProvider;
use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Finders\FileFinder;
use App\Utilities\Managers\FileManager;
use App\Utilities\Managers\IteratorManager;
use PHPUnit\Framework\TestCase;

class FinderUtilitiesAndSessionTest extends TestCase
{
    private array $pathsToDelete = [];

    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }

        rsort($this->pathsToDelete);

        foreach ($this->pathsToDelete as $path) {
            if (is_file($path)) {
                @unlink($path);
                continue;
            }

            if (is_dir($path)) {
                @rmdir($path);
            }
        }

        $this->pathsToDelete = [];
    }

    public function testFileManagerReadsLinesMovesRegularFilesAndNormalizesPaths(): void
    {
        $fileManager = new FileManager();
        $source = tempnam(sys_get_temp_dir(), 'langeler-file-');
        self::assertIsString($source);
        file_put_contents($source, "alpha\nbeta\n");

        $destinationDirectory = sys_get_temp_dir() . '/LangelerMVC/FileManagerTest';
        $destination = $destinationDirectory . '/nested/../moved.txt';

        $this->pathsToDelete[] = $fileManager->normalizePath($destinationDirectory . '/moved.txt');
        $this->pathsToDelete[] = $fileManager->normalizePath($destinationDirectory);

        self::assertSame(["alpha\n", "beta\n"], $fileManager->readLines($source));
        self::assertTrue($fileManager->moveFile($source, $destination));
        self::assertSame(
            $fileManager->normalizePath($destinationDirectory . '/moved.txt'),
            $fileManager->normalizePath($destination)
        );
        self::assertSame('alpha' . PHP_EOL . 'beta' . PHP_EOL, file_get_contents($fileManager->normalizePath($destination)));
    }

    public function testFindersRespectTypeSortingDepthAndCache(): void
    {
        $root = sys_get_temp_dir() . '/LangelerMVC/FinderTest-' . uniqid();
        $alpha = $root . '/alpha';
        $beta = $root . '/beta';
        $nested = $beta . '/nested';

        foreach ([$root, $alpha, $beta, $nested] as $directory) {
            mkdir($directory, 0777, true);
            $this->pathsToDelete[] = $directory;
        }

        file_put_contents($root . '/root.txt', 'root');
        file_put_contents($alpha . '/zeta.txt', 'zeta');
        file_put_contents($alpha . '/alpha.txt', 'alpha');
        file_put_contents($nested . '/deep.txt', 'deep');

        $this->pathsToDelete[] = $root . '/root.txt';
        $this->pathsToDelete[] = $alpha . '/zeta.txt';
        $this->pathsToDelete[] = $alpha . '/alpha.txt';
        $this->pathsToDelete[] = $nested . '/deep.txt';

        $fileFinder = new FileFinder(new IteratorManager());
        $directoryFinder = new DirectoryFinder(new IteratorManager());

        $sortedFiles = $fileFinder->find(['extension' => 'txt'], $root, ['callback' => 'name']);
        $sortedNames = array_map(fn(\SplFileInfo $file): string => $file->getFilename(), array_values($sortedFiles));

        self::assertSame(['alpha.txt', 'deep.txt', 'root.txt', 'zeta.txt'], $sortedNames);

        $depthLimitedFiles = $fileFinder->findByDepth([], $root, 0);
        self::assertSame(['root.txt'], array_map(
            fn(\SplFileInfo $file): string => $file->getFilename(),
            array_values($depthLimitedFiles)
        ));

        $cachedDirectory = $directoryFinder->findByCache(['name' => 'beta'], $root);

        self::assertCount(1, $cachedDirectory);
        self::assertSame('beta', current($cachedDirectory)->getFilename());
    }

    public function testConfigUsesCachedLookupAndSessionCoreServiceWorks(): void
    {
        $provider = new CoreProvider();
        $provider->registerServices();

        $config = $provider->getCoreService('config');
        $session = $provider->getCoreService('session');

        self::assertInstanceOf(Config::class, $config);
        self::assertInstanceOf(Session::class, $session);
        self::assertSame('fallback', $config->get('missing', null, 'fallback'));

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        self::assertTrue($session->start());
        $session->put('framework', 'alive');

        self::assertTrue($session->has('framework'));
        self::assertSame('alive', $session->get('framework'));
        self::assertArrayHasKey('framework', $session->all());

        $session->forget('framework');

        self::assertFalse($session->has('framework'));
        self::assertTrue($session->invalidate());
    }
}
