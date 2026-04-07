<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Abstracts\Http\Request;
use App\Abstracts\Http\Response;
use App\Abstracts\Presentation\Presenter;
use App\Abstracts\Presentation\View;
use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Finders\FileFinder;
use App\Utilities\Handlers\DataHandler;
use App\Utilities\Managers\CacheManager;
use App\Utilities\Managers\DateTimeManager;
use App\Utilities\Managers\FileManager;
use App\Utilities\Managers\IteratorManager;
use App\Utilities\Sanitation\GeneralSanitizer;
use App\Utilities\Sanitation\PatternSanitizer;
use App\Utilities\Validation\GeneralValidator;
use App\Utilities\Validation\PatternValidator;
use PHPUnit\Framework\TestCase;

class HttpPresentationSurfaceTest extends TestCase
{
    private array $pathsToDelete = [];

    protected function tearDown(): void
    {
        foreach ($this->pathsToDelete as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $this->pathsToDelete = [];
    }

    public function testResponseAutoloadsAndTransformsXmlContent(): void
    {
        $response = new class(new DataHandler(), new DateTimeManager(), 202, ['X-Test' => ' Value '], '<root><item>ok</item></root>') extends Response {
            public function send(): void
            {
            }

            public function snapshot(): array
            {
                $this->prepareForSend();

                return $this->toArray();
            }
        };

        $snapshot = $response->snapshot();

        self::assertSame(202, $snapshot['status']);
        self::assertSame('application/xml', $snapshot['headers']['content-type']);
        self::assertSame('Value', $snapshot['headers']['x-test']);
        self::assertSame('<root><item>ok</item></root>', $snapshot['content']);
    }

    public function testPresenterCanExtractKeysAndFormatXml(): void
    {
        $presenter = new class(new DataHandler(), new DateTimeManager(), ['title' => 'Framework', 'body' => null]) extends Presenter {
            public function transform(): array
            {
                return $this->normalizeData();
            }

            public function addComputedProperties(): array
            {
                return $this->state;
            }

            public function addMetadata(): array
            {
                $this->addTimestamps();

                return $this->getMetadata();
            }

            public function prepare(): array
            {
                return $this->transform();
            }

            public function get(string $key, mixed $default = null): mixed
            {
                return $this->state[$key] ?? $default;
            }

            public function extracted(array $keys): array
            {
                return $this->extractKeys($keys);
            }

            public function formatAs(array $data, string $format): mixed
            {
                return $this->formatData($data, $format);
            }
        };

        self::assertSame(['title' => 'Framework'], $presenter->extracted(['title']));
        self::assertStringContainsString('<title>Framework</title>', (string) $presenter->formatAs(['title' => 'Framework'], 'xml'));
        self::assertArrayHasKey('createdAt', $presenter->addMetadata());
    }

    public function testRequestValidatesAndProcessesRegularFiles(): void
    {
        $iteratorManager = new IteratorManager();
        $directoryFinder = new DirectoryFinder($iteratorManager);
        $fileManager = new FileManager();

        $sourcePath = tempnam(sys_get_temp_dir(), 'langeler-request-');
        self::assertIsString($sourcePath);
        file_put_contents($sourcePath, 'framework');

        $request = new class(
            new GeneralSanitizer(),
            new PatternSanitizer(),
            new GeneralValidator(),
            new PatternValidator(),
            $fileManager,
            $directoryFinder,
            [],
            [
                'document' => [
                    'tmp_name' => $sourcePath,
                    'name' => 'codex-' . uniqid('', true) . '.txt',
                ],
            ],
            [
                'ext' => ['txt'],
                'max' => 2048,
                'resize' => ['w' => 128, 'h' => 128],
                'strip' => false,
            ]
        ) extends Request {
            public function validateUpload(string $key): bool
            {
                return $this->validateFile($key);
            }

            public function storeUpload(string $key): string
            {
                return $this->processFile($key);
            }
        };

        self::assertTrue($request->validateUpload('document'));

        $storedPath = $request->storeUpload('document');
        $this->pathsToDelete[] = $storedPath;

        self::assertFileExists($storedPath);
        self::assertStringContainsString('/Storage/Uploads/', str_replace('\\', '/', $storedPath));
        self::assertSame('framework', file_get_contents($storedPath));
    }

    public function testViewResolvesCapitalizedTemplateDirectories(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $layoutPath = $projectRoot . '/App/Templates/Layouts/CodexViewTest.php';
        file_put_contents($layoutPath, '<h1>layout</h1>');
        $this->pathsToDelete[] = $layoutPath;

        $view = new class(
            new FileFinder(new IteratorManager()),
            new DirectoryFinder(new IteratorManager()),
            $this->createStub(CacheManager::class),
            new FileManager(),
            new PatternSanitizer(),
            new PatternValidator()
        ) extends View {
            public function renderLayout(string $layout, array $data = []): string
            {
                return $this->getLayoutPath($layout);
            }

            public function renderPage(string $page, array $data = []): string
            {
                return $this->getPagePath($page);
            }

            public function renderPartial(string $partial, array $data = []): string
            {
                return $this->getPartialPath($partial);
            }

            public function renderComponent(string $component, array $data = []): string
            {
                return $this->getComponentPath($component);
            }

            public function renderAsset(string $type, string $asset): string
            {
                return $asset;
            }

            public function setGlobals(array $variables): void
            {
                $this->globals = $variables;
            }

            public function getGlobals(): array
            {
                return $this->globals;
            }

            public function cacheTemplate(string $key, string $content, ?int $ttl = null): void
            {
            }

            public function fetchCachedTemplate(string $key): ?string
            {
                return null;
            }
        };

        self::assertSame(realpath($layoutPath), realpath($view->renderLayout('CodexViewTest')));
    }
}
