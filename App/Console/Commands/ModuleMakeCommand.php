<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Abstracts\Console\Command;
use App\Utilities\Managers\System\FileManager;
use App\Utilities\Traits\ApplicationPathTrait;

class ModuleMakeCommand extends Command
{
    use ApplicationPathTrait;

    /**
     * @var list<string>
     */
    private const SURFACES = [
        'Controllers',
        'Middlewares',
        'Migrations',
        'Models',
        'Presenters',
        'Repositories',
        'Requests',
        'Responses',
        'Routes',
        'Seeds',
        'Services',
        'Views',
    ];

    public function __construct(private readonly FileManager $fileManager)
    {
    }

    public function name(): string
    {
        return 'module:make';
    }

    public function description(): string
    {
        return 'Scaffold a new module with the framework-standard backend structure and starter pipeline classes.';
    }

    public function handle(array $arguments = [], array $options = []): int
    {
        $rawName = isset($arguments[0]) ? (string) $arguments[0] : '';
        $quiet = isset($options['quiet']) && $this->toBoolean($options['quiet']);

        if ($rawName === '') {
            $this->error('Missing module name. Usage: php console module:make Blog');
            return 1;
        }

        $normalized = $this->normalizeModuleName($rawName);

        if ($normalized === null) {
            $this->error('Invalid module name. Use letters and numbers, for example: Blog or BlogModule.');
            return 1;
        }

        $force = isset($options['force']) && $this->toBoolean($options['force']);
        $moduleName = $normalized['module'];
        $baseName = $normalized['base'];
        $slug = $this->toKebabCase($baseName);
        $modulePath = $this->frameworkBasePath() . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . $moduleName;

        if ($this->fileManager->isDirectory($modulePath) && !$force) {
            $this->error(sprintf('Module [%s] already exists. Use --force to refresh scaffold files.', $moduleName));
            return 1;
        }

        if (!$this->ensureDirectory($modulePath)) {
            return 1;
        }

        $createdDirectories = [];

        foreach (self::SURFACES as $surface) {
            $surfacePath = $modulePath . DIRECTORY_SEPARATOR . $surface;

            if (!$this->ensureDirectory($surfacePath)) {
                return 1;
            }

            $createdDirectories[] = $surfacePath;

            $readmePath = $surfacePath . DIRECTORY_SEPARATOR . 'README.md';
            $readme = $this->surfaceReadme($moduleName, $surface);

            if (!$this->writeFile($readmePath, $readme, $force)) {
                return 1;
            }
        }

        if (!$this->writeFile($modulePath . DIRECTORY_SEPARATOR . 'README.md', $this->moduleReadme($moduleName), $force)) {
            return 1;
        }

        $files = $this->starterFiles($moduleName, $baseName, $slug);

        foreach ($files as $path => $content) {
            if (!$this->writeFile($path, $content, $force)) {
                return 1;
            }
        }

        if (!$quiet) {
            $this->info(sprintf('Scaffolded module [%s] at %s', $moduleName, $modulePath));
            $this->line(sprintf('Created/verified %d module surfaces.', count($createdDirectories)));
            $this->line('Starter classes: controller, request, service, presenter, view, response, and routes/web.php');
        }

        return 0;
    }

    /**
     * @return array{module:string,base:string}|null
     */
    private function normalizeModuleName(string $raw): ?array
    {
        $segments = preg_split('/[^A-Za-z0-9]+/', $this->trimString($raw)) ?: [];
        $normalized = '';

        foreach ($segments as $segment) {
            $segment = preg_replace('/[^A-Za-z0-9]/', '', $segment);

            if (!is_string($segment) || $segment === '') {
                continue;
            }

            $normalized .= strtoupper(substr($segment, 0, 1)) . substr($segment, 1);
        }

        if ($normalized === '' || !preg_match('/^[A-Za-z]/', $normalized)) {
            return null;
        }

        $base = preg_match('/module$/i', $normalized) === 1
            ? substr($normalized, 0, -6)
            : $normalized;

        if (!is_string($base) || $base === '') {
            return null;
        }

        $base = strtoupper(substr($base, 0, 1)) . substr($base, 1);

        return [
            'module' => $base . 'Module',
            'base' => $base,
        ];
    }

    private function ensureDirectory(string $path): bool
    {
        if ($this->fileManager->isDirectory($path)) {
            return true;
        }

        if ($this->fileManager->createDirectory($path, 0777, true)) {
            return true;
        }

        $this->error(sprintf('Failed to create directory: %s', $path));

        return false;
    }

    private function writeFile(string $path, string $content, bool $force): bool
    {
        if ($this->fileManager->fileExists($path) && !$force) {
            return true;
        }

        $written = $this->fileManager->writeContents($path, $content);

        if ($written !== false) {
            return true;
        }

        $this->error(sprintf('Failed to write file: %s', $path));

        return false;
    }

    private function toKebabCase(string $value): string
    {
        $kebab = preg_replace('/(?<!^)[A-Z]/', '-$0', $value);
        $kebab = is_string($kebab) ? $kebab : $value;
        $kebab = $this->toLower($kebab);

        return trim($kebab, '-');
    }

    private function moduleReadme(string $moduleName): string
    {
        return <<<MD
# {$moduleName}

Scaffolded module generated by `php console module:make`.

Use this slice as the starting point for your domain-specific controllers, services, repositories, and presentation flow.
MD;
    }

    private function surfaceReadme(string $moduleName, string $surface): string
    {
        return <<<MD
# {$surface}

This directory belongs to `{$moduleName}` and follows the LangelerMVC module convention.
MD;
    }

    /**
     * @return array<string, string>
     */
    private function starterFiles(string $moduleName, string $baseName, string $slug): array
    {
        $namespace = "App\\Modules\\{$moduleName}";
        $alias = $this->toLower($slug) . '.index';

        $modulePath = $this->frameworkBasePath() . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Modules' . DIRECTORY_SEPARATOR . $moduleName;

        return [
            $modulePath . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . $baseName . 'Controller.php' => sprintf(
                <<<'PHP'
<?php

declare(strict_types=1);

namespace %s\Controllers;

use App\Abstracts\Http\Controller;
use App\Contracts\Http\ResponseInterface;
use %s\Presenters\%sPresenter;
use %s\Requests\%sRequest;
use %s\Responses\%sResponse;
use %s\Services\%sService;
use %s\Views\%sView;

class %sController extends Controller
{
    public function __construct(
        %sRequest $request,
        %sResponse $response,
        %sService $service,
        %sPresenter $presenter,
        %sView $view
    ) {
        parent::__construct($request, $response, $service, $presenter, $view);
    }

    public function index(): ResponseInterface
    {
        $result = $this->service->execute();
        $payload = $this->isArray($result) ? $result : [
            'status' => 200,
            'headline' => '%s is ready.',
            'summary' => 'Generated module controller.',
            'body' => '',
            'template' => 'Home',
        ];

        return $this->respondWithPresentation(
            $payload,
            'Home',
            null,
            ['X-Module' => '%s']
        );
    }
}
PHP,
                $namespace,
                $namespace,
                $baseName,
                $namespace,
                $baseName,
                $namespace,
                $baseName,
                $namespace,
                $baseName,
                $namespace,
                $baseName,
                $baseName,
                $baseName,
                $baseName,
                $baseName,
                $baseName,
                $baseName,
                $baseName,
                $moduleName
            ),
            $modulePath . DIRECTORY_SEPARATOR . 'Requests' . DIRECTORY_SEPARATOR . $baseName . 'Request.php' => sprintf(
                <<<'PHP'
<?php

declare(strict_types=1);

namespace %s\Requests;

use App\Abstracts\Http\Request;

class %sRequest extends Request
{
}
PHP,
                $namespace,
                $baseName
            ),
            $modulePath . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . $baseName . 'Service.php' => sprintf(
                <<<'PHP'
<?php

declare(strict_types=1);

namespace %s\Services;

use App\Abstracts\Http\Service;

class %sService extends Service
{
    protected function handle(): array
    {
        return [
            'status' => 200,
            'template' => 'Home',
            'headline' => '%s is running.',
            'summary' => 'This module was generated by LangelerMVC module:make and is ready for domain logic.',
            'body' => 'Replace this starter payload with real service orchestration for your use-case.',
            'callToAction' => [
                'label' => 'Go Home',
                'href' => '/',
            ],
        ];
    }
}
PHP,
                $namespace,
                $baseName,
                $moduleName
            ),
            $modulePath . DIRECTORY_SEPARATOR . 'Presenters' . DIRECTORY_SEPARATOR . $baseName . 'Presenter.php' => sprintf(
                <<<'PHP'
<?php

declare(strict_types=1);

namespace %s\Presenters;

use App\Abstracts\Presentation\Presenter;

class %sPresenter extends Presenter
{
}
PHP,
                $namespace,
                $baseName
            ),
            $modulePath . DIRECTORY_SEPARATOR . 'Views' . DIRECTORY_SEPARATOR . $baseName . 'View.php' => sprintf(
                <<<'PHP'
<?php

declare(strict_types=1);

namespace %s\Views;

use App\Abstracts\Presentation\View;

class %sView extends View
{
}
PHP,
                $namespace,
                $baseName
            ),
            $modulePath . DIRECTORY_SEPARATOR . 'Responses' . DIRECTORY_SEPARATOR . $baseName . 'Response.php' => sprintf(
                <<<'PHP'
<?php

declare(strict_types=1);

namespace %s\Responses;

use App\Abstracts\Http\StandardResponse;

class %sResponse extends StandardResponse
{
}
PHP,
                $namespace,
                $baseName
            ),
            $modulePath . DIRECTORY_SEPARATOR . 'Routes' . DIRECTORY_SEPARATOR . 'web.php' => sprintf(
                <<<'PHP'
<?php

declare(strict_types=1);

use App\Core\Router;
use %s\Controllers\%sController;

return static function (Router $router): void {
    $router->addRouteWithAlias('GET', '/%s', %sController::class, 'index', '%s');
};
PHP,
                $namespace,
                $baseName,
                $slug,
                $baseName,
                $alias
            ),
        ];
    }

    private function toBoolean(mixed $value): bool
    {
        if ($this->isBool($value)) {
            return $value;
        }

        if ($this->isInt($value)) {
            return $value !== 0;
        }

        if ($this->isString($value)) {
            $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            return $parsed ?? true;
        }

        return (bool) $value;
    }
}
