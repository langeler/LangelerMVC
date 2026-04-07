<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Abstracts\Http\Controller;
use App\Abstracts\Http\Middleware;
use App\Abstracts\Http\Request;
use App\Abstracts\Http\Response;
use App\Abstracts\Http\Service;
use App\Abstracts\Presentation\Presenter;
use App\Abstracts\Presentation\View;
use App\Contracts\Http\ControllerInterface;
use App\Contracts\Http\RequestInterface;
use App\Contracts\Http\ResponseInterface;
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

class MvcLayerTest extends TestCase
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

    public function testRequestLifecycleProvidesPublicAccessors(): void
    {
        $request = new class(
            new GeneralSanitizer(),
            new PatternSanitizer(),
            new GeneralValidator(),
            new PatternValidator(),
            new FileManager(),
            new DirectoryFinder(new IteratorManager()),
            ['user' => ['name' => 'LangelerMVC']],
            ['avatar' => ['name' => 'avatar.png']],
            [
                'ext' => ['png'],
                'max' => 1024,
                'resize' => ['w' => 128, 'h' => 128],
                'strip' => false,
            ],
            ['X-Trace-Id' => 'trace-123']
        ) extends Request {
            protected function transformInput(array $data): array
            {
                $data['processed'] = true;

                return $data;
            }
        };

        $request->handle();

        self::assertSame('LangelerMVC', $request->input('user.name'));
        self::assertTrue($request->input('processed'));
        self::assertSame('avatar.png', $request->file('avatar.name'));
        self::assertSame('trace-123', $request->header('x-trace-id'));
        self::assertArrayHasKey('user', $request->all());
        self::assertArrayHasKey('x-trace-id', $request->headers());
    }

    public function testPresenterDefaultsSupportFillPrepareAndDotAccess(): void
    {
        $presenter = new class(new DataHandler(), new DateTimeManager(), ['title' => 'LangelerMVC']) extends Presenter {
            protected function computeProperties(array $data): array
            {
                return ['slug' => strtolower((string) ($data['title'] ?? ''))];
            }

            protected function buildMetadata(array $data): array
            {
                return ['fieldCount' => count($data)];
            }
        };

        $prepared = $presenter->prepare();

        self::assertSame('langelermvc', $prepared['slug']);
        self::assertSame(2, $prepared['meta']['fieldCount']);
        self::assertSame(2, $presenter->get('meta.fieldCount'));

        $presenter->fill(['title' => 'Framework']);

        self::assertSame('Framework', $presenter->get('title'));
    }

    public function testViewDefaultsRenderTemplatesAssetsAndCache(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $pagePath = $projectRoot . '/App/Templates/Pages/CodexMvcLayerPage.php';
        $cssPath = $projectRoot . '/App/Resources/css/codex-mvc-layer.css';

        file_put_contents($pagePath, '<h1><?= $shared ?></h1><p><?= $name ?></p>');
        file_put_contents($cssPath, 'body { color: #111; }');

        $this->pathsToDelete[] = $pagePath;
        $this->pathsToDelete[] = $cssPath;

        $cache = $this->createMock(CacheManager::class);
        $cache->expects(self::once())
            ->method('set')
            ->with('page-cache', 'cached markup', 300)
            ->willReturn(true);
        $cache->expects(self::once())
            ->method('get')
            ->with('page-cache')
            ->willReturn('cached markup');

        $view = new class(
            new FileFinder(new IteratorManager()),
            new DirectoryFinder(new IteratorManager()),
            $cache,
            new FileManager(),
            new PatternSanitizer(),
            new PatternValidator()
        ) extends View {
        };

        $view->setGlobals(['shared' => 'LangelerMVC']);

        self::assertSame('<h1>LangelerMVC</h1><p>Framework</p>', $view->renderPage('CodexMvcLayerPage', ['name' => 'Framework']));
        self::assertSame(realpath($cssPath), realpath($view->renderAsset('css', 'codex-mvc-layer.css')));

        $view->cacheTemplate('page-cache', 'cached markup', 300);

        self::assertSame('cached markup', $view->fetchCachedTemplate('page-cache'));
    }

    public function testControllerServiceMiddlewareAndResponseLifecyclesWorkTogether(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $pagePath = $projectRoot . '/App/Templates/Pages/CodexMvcLayerController.php';
        file_put_contents($pagePath, '<h1><?= $headline ?></h1><p><?= $processed ?></p>');
        $this->pathsToDelete[] = $pagePath;

        $request = new class(
            new GeneralSanitizer(),
            new PatternSanitizer(),
            new GeneralValidator(),
            new PatternValidator(),
            new FileManager(),
            new DirectoryFinder(new IteratorManager()),
            ['message' => 'LangelerMVC'],
            [],
            [
                'ext' => ['txt'],
                'max' => 1024,
                'resize' => ['w' => 0, 'h' => 0],
                'strip' => false,
            ]
        ) extends Request {
            protected function transformInput(array $data): array
            {
                $data['processed'] = true;

                return $data;
            }
        };

        $service = new class($request) extends Service {
            private RequestInterface $request;

            protected function initialize(...$dependencies): void
            {
                $this->request = $dependencies[0];
            }

            protected function handle(): mixed
            {
                return [
                    'message' => $this->request->input('message', ''),
                    'processed' => $this->request->input('processed', false) ? 'yes' : 'no',
                ];
            }
        };

        $presenter = new class(new DataHandler(), new DateTimeManager()) extends Presenter {
            protected function computeProperties(array $data): array
            {
                return ['headline' => strtoupper((string) ($data['message'] ?? ''))];
            }

            protected function buildMetadata(array $data): array
            {
                return ['processed' => $data['processed'] ?? 'no'];
            }
        };

        $view = new class(
            new FileFinder(new IteratorManager()),
            new DirectoryFinder(new IteratorManager()),
            $this->createStub(CacheManager::class),
            new FileManager(),
            new PatternSanitizer(),
            new PatternValidator()
        ) extends View {
        };

        $response = new class(new DataHandler(), new DateTimeManager()) extends Response {
            public function send(): void
            {
                $this->prepareForSend();
            }
        };

        $controller = new class($request, $response, $service, $presenter, $view) extends Controller {
            protected function finalize(mixed $result): ResponseInterface
            {
                return $this->respondWithView(
                    'renderPage',
                    'CodexMvcLayerController',
                    $this->preparePresenterData('prepare', $result),
                    201,
                    ['X-Controller' => 'ok']
                );
            }
        };

        $middleware = new class($controller) extends Middleware {
            /**
             * @var list<string>
             */
            private array $events = [];

            public function __construct(private ControllerInterface $controller)
            {
            }

            public function events(): array
            {
                return $this->events;
            }

            protected function authenticate(): void
            {
                $this->events[] = 'authenticate';
            }

            protected function authorize(): void
            {
                $this->events[] = 'authorize';
            }

            protected function before(): void
            {
                $this->events[] = 'before';
            }

            protected function process(): ResponseInterface
            {
                $this->events[] = 'process';

                return $this->controller->run();
            }

            protected function after(ResponseInterface $response): ResponseInterface
            {
                $this->events[] = 'after';
                $response->addHeader('X-Middleware', 'done');

                return $response;
            }
        };

        $handledResponse = $middleware->handle();
        $handledResponse->prepareForSend();
        $snapshot = $handledResponse->toArray();

        self::assertSame(
            ['authenticate', 'authorize', 'before', 'process', 'after'],
            $middleware->events()
        );
        self::assertSame(201, $snapshot['status']);
        self::assertSame('text/html; charset=UTF-8', $snapshot['headers']['content-type']);
        self::assertSame('ok', $snapshot['headers']['x-controller']);
        self::assertSame('done', $snapshot['headers']['x-middleware']);
        self::assertStringContainsString('<h1>LANGELERMVC</h1>', $snapshot['content']);
        self::assertStringContainsString('<p>yes</p>', $snapshot['content']);
    }
}
