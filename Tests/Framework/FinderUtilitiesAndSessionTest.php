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

        $copyDestination = $destinationDirectory . '/copies/copied.txt';
        $this->pathsToDelete[] = $fileManager->normalizePath($destinationDirectory . '/copies/copied.txt');
        $this->pathsToDelete[] = $fileManager->normalizePath($destinationDirectory . '/copies');

        self::assertTrue($fileManager->copyFile($fileManager->normalizePath($destination), $copyDestination));
        self::assertSame(
            'alpha' . PHP_EOL . 'beta' . PHP_EOL,
            file_get_contents($fileManager->normalizePath($copyDestination))
        );
    }

    public function testIteratorManagerAppliesSettingsAndFileManagerMaintainsCursorState(): void
    {
        $iteratorManager = new IteratorManager();
        $arrayIterator = $iteratorManager->createIterator('ArrayIterator', ['flag' => ['stdProps' => true]], ['a' => 1]);
        $directory = sys_get_temp_dir() . '/LangelerMVC/IteratorManager-' . uniqid();
        $fileManager = new FileManager();
        $file = tempnam(sys_get_temp_dir(), 'langeler-cursor-');

        self::assertIsString($file);
        file_put_contents($file, "alpha\nbeta\ngamma\n");
        $this->pathsToDelete[] = $file;
        mkdir($directory, 0777, true);
        file_put_contents($directory . '/sample.txt', 'sample');
        $this->pathsToDelete[] = $directory . '/sample.txt';
        $this->pathsToDelete[] = $directory;

        self::assertSame(\ArrayIterator::STD_PROP_LIST, $arrayIterator->getFlags());

        $fileManager->moveToLine($file, 1);
        self::assertSame("beta\n", $fileManager->readLine($file));
        self::assertSame(2, $fileManager->getLineNumber($file));

        $fileManager->resetPointer($file);
        self::assertSame("alpha\n", $fileManager->readLine($file));

        $recursiveIterator = $iteratorManager->RecursiveIteratorIterator(
            $iteratorManager->RecursiveDirectoryIterator($directory, ['flag' => ['skipDots' => true]])
        );
        $iteratorManager->setIterator($recursiveIterator);
        $iteratorManager->rewind();

        self::assertTrue($iteratorManager->valid());
        self::assertTrue($iteratorManager->isFile());
        self::assertFalse($iteratorManager->isDir());
        self::assertSame(filesize($directory . '/sample.txt'), $iteratorManager->getSize());
        self::assertSame(realpath($directory . '/sample.txt'), $iteratorManager->getRealPath());
        self::assertGreaterThan(0, $iteratorManager->getPermissions());
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

        self::assertSame(realpath(dirname(__DIR__, 2)), $fileFinder->getRoot());

        $sortedFiles = $fileFinder->find(['extension' => 'txt'], $root, ['callback' => 'name']);
        $sortedNames = array_map(fn(\SplFileInfo $file): string => $file->getFilename(), array_values($sortedFiles));

        self::assertSame(['alpha.txt', 'deep.txt', 'root.txt', 'zeta.txt'], $sortedNames);

        $depthLimitedFiles = $fileFinder->findByDepth([], $root, 0);
        self::assertSame(['root.txt'], array_map(
            fn(\SplFileInfo $file): string => $file->getFilename(),
            array_values($depthLimitedFiles)
        ));

        $depthScopedFiles = $fileFinder->findByDepth(['depth' => 1], $root, 2, ['callback' => 'name']);
        self::assertSame(['alpha.txt', 'root.txt', 'zeta.txt'], array_map(
            fn(\SplFileInfo $file): string => $file->getFilename(),
            array_values($depthScopedFiles)
        ));

        $depthCriteriaFiles = $fileFinder->find(['depth' => 0], $root);
        self::assertSame(['root.txt'], array_map(
            fn(\SplFileInfo $file): string => $file->getFilename(),
            array_values($depthCriteriaFiles)
        ));

        $cachedDirectory = $directoryFinder->findByCache(['name' => 'beta'], $root);

        self::assertCount(1, $cachedDirectory);
        self::assertSame('beta', current($cachedDirectory)->getFilename());

        $tree = $directoryFinder->tree($root);
        self::assertIsString($tree);
        self::assertStringContainsString('alpha', $tree);

        $bufferLevel = ob_get_level();
        ob_start();
        try {
            $directoryFinder->showTree($root);
            $streamedTree = ob_get_clean();
        } finally {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }
        }

        self::assertSame($tree, $streamedTree);
        self::assertSame($tree, $directoryFinder->tree($root));
        self::assertCount(1, $fileFinder->scan($root));
        self::assertCount(3, $directoryFinder->scan($root));

        $fileMetadata = $fileFinder->scan($alpha);
        self::assertSame('file', $fileMetadata[0]['type']);
        self::assertArrayHasKey('path', $fileMetadata[0]);
        self::assertArrayHasKey('modifiedAt', $fileMetadata[0]);

        $multiPathSearch = $fileFinder->search(['extension' => 'txt'], [$alpha, $nested], ['callback' => 'name']);
        self::assertSame(['alpha.txt', 'deep.txt', 'zeta.txt'], array_map(
            fn(\SplFileInfo $file): string => $file->getFilename(),
            array_values($multiPathSearch)
        ));

        $sortedCachedFiles = $fileFinder->findByCache(['extension' => 'txt'], $root, ['callback' => 'name']);
        self::assertSame(['alpha.txt', 'deep.txt', 'root.txt', 'zeta.txt'], array_map(
            fn(\SplFileInfo $file): string => $file->getFilename(),
            array_values($sortedCachedFiles)
        ));

        $regexFiles = $fileFinder->findByPattern(['pattern' => '/alpha|deep/'], $root, ['callback' => 'name']);
        self::assertSame(['alpha.txt', 'deep.txt'], array_map(
            fn(\SplFileInfo $file): string => $file->getFilename(),
            array_values($regexFiles)
        ));

        $depthState = new \ReflectionProperty($fileFinder, 'itemDepths');
        $depthState->setAccessible(true);
        $depthCountAfterPatternSearch = count($depthState->getValue($fileFinder));

        $fileFinder->find(['extension' => 'txt'], $root, ['callback' => 'name']);
        self::assertSame($depthCountAfterPatternSearch, count($depthState->getValue($fileFinder)));

        $fileFinder->useCache(false)->clearCache();
        self::assertSame(['alpha.txt', 'deep.txt'], array_map(
            fn(\SplFileInfo $file): string => $file->getFilename(),
            array_values($fileFinder->findByRegEx(['pattern' => '/alpha|deep/'], $root, ['callback' => 'name']))
        ));
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
