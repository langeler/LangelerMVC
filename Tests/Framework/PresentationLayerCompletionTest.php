<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Abstracts\Http\Controller;
use App\Abstracts\Http\Response;
use App\Abstracts\Presentation\Presenter;
use App\Abstracts\Presentation\Resource;
use App\Abstracts\Presentation\ResourceCollection;
use App\Abstracts\Presentation\View;
use App\Contracts\Http\RequestInterface;
use App\Contracts\Http\ResponseInterface;
use App\Contracts\Http\ServiceInterface;
use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Finders\FileFinder;
use App\Utilities\Handlers\DataHandler;
use App\Utilities\Managers\CacheManager;
use App\Utilities\Managers\DateTimeManager;
use App\Utilities\Managers\FileManager;
use App\Utilities\Managers\IteratorManager;
use App\Utilities\Sanitation\PatternSanitizer;
use App\Utilities\Validation\PatternValidator;
use PHPUnit\Framework\TestCase;

class PresentationLayerCompletionTest extends TestCase
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

    public function testViewSupportsDefaultLayoutsSharedTemplateHelpersAndTemplateLookup(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $layoutPath = $projectRoot . '/App/Templates/Layouts/CodexPresentationLayout.php';
        $pagePath = $projectRoot . '/App/Templates/Pages/CodexPresentationPage.php';
        $partialPath = $projectRoot . '/App/Templates/Partials/CodexPresentationMessage.php';
        $componentPath = $projectRoot . '/App/Templates/Components/CodexPresentationList.php';

        file_put_contents($layoutPath, '<article><?= $content ?? "" ?></article>');
        file_put_contents($partialPath, '<p><?= $view->escape((string) ($message ?? "")) ?></p>');
        file_put_contents($componentPath, '<ul><?php foreach (($items ?? []) as $item): ?><li><?= $view->escape($item) ?></li><?php endforeach; ?></ul>');
        file_put_contents(
            $pagePath,
            '<?= $view->renderPartial("CodexPresentationMessage", ["message" => $shared ?? ""]) ?>'
            . '<h1><?= $view->escape((string) ($name ?? "")) ?></h1>'
            . '<?= $view->renderComponent("CodexPresentationList", ["items" => ["alpha", "beta"]]) ?>'
        );

        $this->pathsToDelete = [$layoutPath, $pagePath, $partialPath, $componentPath];

        $view = new class(
            new FileFinder(new IteratorManager()),
            new DirectoryFinder(new IteratorManager()),
            $this->createStub(CacheManager::class),
            new FileManager(),
            new PatternSanitizer(),
            new PatternValidator()
        ) extends View {
        };

        $view->share('shared', 'LangelerMVC')->setDefaultLayout('CodexPresentationLayout');

        self::assertTrue($view->templateExists('layout', 'CodexPresentationLayout'));
        self::assertTrue($view->templateExists('partial', 'CodexPresentationMessage'));
        self::assertSame('<p>LangelerMVC</p><h1>Framework</h1><ul><li>alpha</li><li>beta</li></ul>', $view->renderPageContent('CodexPresentationPage', ['name' => 'Framework']));
        self::assertSame('<article><p>LangelerMVC</p><h1>Framework</h1><ul><li>alpha</li><li>beta</li></ul></article>', $view->renderPage('CodexPresentationPage', ['name' => 'Framework']));
    }

    public function testResourcePrimitivesSupportMetaLinksAdditionalAndPagination(): void
    {
        $resource = new class(['name' => 'LangelerMVC']) extends Resource {
            protected function resolveData(): array
            {
                return is_array($this->resource) ? $this->resource : [];
            }

            protected function defaultMeta(): array
            {
                return ['module' => 'Tests'];
            }
        };

        $collection = new class([['id' => 1], ['id' => 2]]) extends ResourceCollection {
            protected function mapItem(mixed $item): array
            {
                return (array) $item;
            }
        };

        self::assertSame(
            [
                'data' => ['name' => 'LangelerMVC'],
                'links' => ['self' => '/api/test'],
                'meta' => ['module' => 'Tests', 'api' => true],
                'jsonapi' => ['version' => '1.0'],
            ],
            $resource
                ->withMeta(['api' => true])
                ->withLinks(['self' => '/api/test'])
                ->additional(['jsonapi' => ['version' => '1.0']])
                ->toArray()
        );

        self::assertSame(
            [
                'data' => [['id' => 1], ['id' => 2]],
                'meta' => ['pagination' => ['page' => 1, 'perPage' => 15]],
            ],
            $collection->withPagination(['page' => 1, 'perPage' => 15])->toArray()
        );
    }

    public function testControllerPresentationHelperNegotiatesHtmlAndJson(): void
    {
        $htmlRequest = new class implements RequestInterface {
            public function sanitize(): void {}
            public function validate(): void {}
            public function transform(): void {}
            public function handle(): void {}
            public function input(string $key, mixed $default = null): mixed { return $default; }
            public function all(): array { return []; }
            public function file(?string $key = null): mixed { return null; }
            public function header(string $key, mixed $default = null): mixed { return $default; }
            public function headers(): array { return []; }
            public function method(): string { return 'GET'; }
            public function uri(): string { return '/'; }
            public function accepts(string $contentType): bool { return false; }
            public function wantsJson(): bool { return false; }
            public function expectsJson(): bool { return false; }
        };

        $jsonRequest = new class($htmlRequest) implements RequestInterface {
            public function __construct(private RequestInterface $request) {}
            public function sanitize(): void { $this->request->sanitize(); }
            public function validate(): void { $this->request->validate(); }
            public function transform(): void { $this->request->transform(); }
            public function handle(): void { $this->request->handle(); }
            public function input(string $key, mixed $default = null): mixed { return $this->request->input($key, $default); }
            public function all(): array { return $this->request->all(); }
            public function file(?string $key = null): mixed { return $this->request->file($key); }
            public function header(string $key, mixed $default = null): mixed { return $this->request->header($key, $default); }
            public function headers(): array { return ['accept' => 'application/json']; }
            public function method(): string { return $this->request->method(); }
            public function uri(): string { return $this->request->uri(); }
            public function accepts(string $contentType): bool { return str_contains(strtolower($contentType), 'json'); }
            public function wantsJson(): bool { return true; }
            public function expectsJson(): bool { return true; }
        };

        $response = new class(new DataHandler(), new DateTimeManager()) extends Response {
            public function send(): void
            {
                $this->prepareForSend();
            }
        };

        $presenter = new class(new DataHandler(), new DateTimeManager()) extends Presenter {
            protected function buildMetadata(array $data): array
            {
                return ['module' => 'Tests'];
            }
        };

        $view = $this->createStub(\App\Contracts\Presentation\ViewInterface::class);
        $view->method('renderPage')->willReturn('<p>html</p>');

        $service = $this->createStub(ServiceInterface::class);

        $controller = new class($htmlRequest, $response, $service, $presenter, $view) extends Controller {
            public function finalizePayload(array $payload, mixed $resource = null): ResponseInterface
            {
                return $this->respondWithPresentation($payload, 'Ignored', $resource, ['X-Test' => 'presentation']);
            }
        };

        $html = $controller->finalizePayload(['status' => 202, 'template' => 'Ignored', 'title' => 'HTML']);

        self::assertSame(202, $html->getStatus());
        self::assertSame('text/html; charset=UTF-8', $html->getHeaders()['content-type']);
        self::assertSame('<p>html</p>', $html->getContent());

        $jsonController = new class($jsonRequest, $response, $service, $presenter, $view) extends Controller {
            public function finalizePayload(array $payload, mixed $resource = null): ResponseInterface
            {
                return $this->respondWithPresentation($payload, 'Ignored', $resource, ['X-Test' => 'presentation']);
            }
        };

        $jsonResource = new class(['status' => 201, 'title' => 'JSON']) extends Resource {
            protected function resolveData(): array
            {
                return is_array($this->resource) ? $this->resource : [];
            }
        };

        $json = $jsonController->finalizePayload(
            ['status' => 201, 'title' => 'JSON'],
            $jsonResource
        );

        self::assertSame(201, $json->getStatus());
        self::assertSame('application/json; charset=UTF-8', $json->getHeaders()['content-type']);
        self::assertSame(['data' => ['status' => 201, 'title' => 'JSON']], $json->getContent());
    }
}
