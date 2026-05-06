<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Support;

use App\Contracts\Support\FrameworkLayerManagerInterface;
use App\Utilities\Managers\FileManager;
use App\Utilities\Traits\ApplicationPathTrait;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\ManipulationTrait;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class FrameworkLayerManager implements FrameworkLayerManagerInterface
{
    use ApplicationPathTrait;
    use ArrayTrait;
    use ManipulationTrait;

    /**
     * @var array<string, array<string, mixed>>
     */
    private const LAYERS = [
        'public_bootstrap' => [
            'label' => 'Public / Bootstrap',
            'responsibility' => 'Thin HTTP and console entrypoints hand off into the framework bootstrap.',
            'required_paths' => [
                'Public/index.php',
                'Public/.htaccess',
                'bootstrap/app.php',
                'bootstrap/console.php',
                'console',
            ],
        ],
        'core_runtime' => [
            'label' => 'Core Runtime',
            'responsibility' => 'Application lifecycle, config, routing, database, session, migrations, and seeds.',
            'required_paths' => [
                'App/Core/App.php',
                'App/Core/Bootstrap.php',
                'App/Core/Config.php',
                'App/Core/Container.php',
                'App/Core/Database.php',
                'App/Core/Router.php',
                'App/Core/MigrationRunner.php',
                'App/Core/SeedRunner.php',
                'App/Core/Session.php',
            ],
        ],
        'providers_container' => [
            'label' => 'Providers / Container',
            'responsibility' => 'Service registration, provider composition, and plug-and-play adapter resolution.',
            'required_paths' => [
                'App/Providers/CoreProvider.php',
                'App/Providers/ModuleProvider.php',
                'App/Providers/ExceptionProvider.php',
                'App/Providers/CacheProvider.php',
                'App/Providers/CryptoProvider.php',
                'App/Providers/QueueProvider.php',
                'App/Providers/NotificationProvider.php',
                'App/Providers/PaymentProvider.php',
                'App/Providers/ShippingProvider.php',
            ],
        ],
        'contracts_abstracts' => [
            'label' => 'Contracts / Abstracts',
            'responsibility' => 'Typed extension seams and reusable framework base classes.',
            'required_paths' => [
                'App/Contracts',
                'App/Contracts/Support/FrameworkLayerManagerInterface.php',
                'App/Abstracts',
                'App/Abstracts/Http',
                'App/Abstracts/Presentation',
                'App/Abstracts/Database',
            ],
        ],
        'http_mvc' => [
            'label' => 'HTTP / MVC',
            'responsibility' => 'Request, response, controller, middleware, service, route, and negotiated response flow.',
            'required_paths' => [
                'App/Contracts/Http',
                'App/Abstracts/Http',
                'App/Core/FrameworkResponse.php',
                'App/Modules/WebModule/Controllers/HomeController.php',
                'App/Modules/WebModule/Routes/web.php',
            ],
        ],
        'presentation' => [
            'label' => 'Presentation / View / Theme / Assets',
            'responsibility' => 'Native .vide templates, view composition, safe HTML helpers, theme globals, and public assets.',
            'required_paths' => [
                'App/Abstracts/Presentation/View.php',
                'App/Contracts/Presentation/ViewInterface.php',
                'App/Utilities/Managers/Presentation/TemplateEngine.php',
                'App/Utilities/Managers/Presentation/AssetManager.php',
                'App/Utilities/Managers/Presentation/HtmlManager.php',
                'App/Utilities/Managers/Presentation/ThemeManager.php',
                'App/Templates/Layouts',
                'App/Templates/Pages',
                'App/Templates/Partials',
                'App/Templates/Components',
                'App/Resources/css/langelermvc-theme.css',
                'App/Resources/js/langelermvc-theme.js',
                'Public/assets/css/langelermvc-theme.css',
                'Public/assets/js/langelermvc-theme.js',
            ],
        ],
        'data_persistence' => [
            'label' => 'Data / Persistence',
            'responsibility' => 'Models, repositories, migrations, seeds, query builders, and release SQL references.',
            'required_paths' => [
                'App/Abstracts/Database',
                'App/Core/Schema',
                'App/Utilities/Query',
                'App/Framework/Migrations',
                'Data/Framework.sql',
                'Data/Web.sql',
                'Data/Users.sql',
                'Data/Products.sql',
                'Data/Carts.sql',
                'Data/Orders.sql',
            ],
        ],
        'security_auth' => [
            'label' => 'Security / Auth',
            'responsibility' => 'Authentication, authorization, RBAC, signed URLs, throttling, sessions, OTP, and passkeys.',
            'required_paths' => [
                'App/Contracts/Auth',
                'App/Utilities/Managers/Security',
                'App/Utilities/Managers/Support/OtpManager.php',
                'App/Utilities/Managers/Support/PasskeyManager.php',
                'App/Drivers/Passkeys',
                'Config/auth.php',
                'Config/http.php',
                'App/Modules/UserModule',
            ],
        ],
        'drivers' => [
            'label' => 'Drivers',
            'responsibility' => 'Low-level adapters for cache, crypto, notifications, payments, passkeys, queues, sessions, and shipping.',
            'required_paths' => [
                'App/Drivers/Caching',
                'App/Drivers/Cryptography',
                'App/Drivers/Notifications',
                'App/Drivers/Passkeys',
                'App/Drivers/Payments',
                'App/Drivers/Queue',
                'App/Drivers/Session',
                'App/Drivers/Shipping',
            ],
        ],
        'utilities_managers' => [
            'label' => 'Utilities / Managers',
            'responsibility' => 'Shared manager, trait, finder, handler, sanitation, validation, and query tooling.',
            'required_paths' => [
                'App/Utilities/Managers',
                'App/Utilities/Managers/Commerce',
                'App/Utilities/Managers/Presentation',
                'App/Utilities/Managers/Support',
                'App/Utilities/Managers/System',
                'App/Utilities/Traits',
                'App/Utilities/Finders',
                'App/Utilities/Handlers',
                'App/Utilities/Sanitation',
                'App/Utilities/Validation',
            ],
        ],
        'modules' => [
            'label' => 'Modules',
            'responsibility' => 'First-party application slices using the repeated module backend shape.',
            'required_paths' => [
                'App/Modules/WebModule',
                'App/Modules/UserModule',
                'App/Modules/AdminModule',
                'App/Modules/ShopModule',
                'App/Modules/CartModule',
                'App/Modules/OrderModule',
            ],
        ],
        'installer' => [
            'label' => 'Installer',
            'responsibility' => 'Guided first-run setup for environment, database, modules, admin, integrations, and theme defaults.',
            'required_paths' => [
                'App/Installer/InstallerWizard.php',
                'App/Installer/InstallerView.php',
                'Public/install/index.php',
                'App/Templates/Pages/InstallerWizard.vide',
                'Docs/InstallationWizard.md',
            ],
        ],
        'console_ops' => [
            'label' => 'Console / Operations',
            'responsibility' => 'Operational CLI, health, audit, queues, events, notifications, release checks, and readiness commands.',
            'required_paths' => [
                'App/Console/ConsoleKernel.php',
                'App/Console/Commands',
                'App/Utilities/Managers/Support/HealthManager.php',
                'App/Utilities/Managers/Support/AuditLogger.php',
                'App/Utilities/Managers/Async',
                'Config/operations.php',
                'Docs/OperationsGuide.md',
            ],
        ],
        'release_docs_data' => [
            'label' => 'Release / Docs / Data',
            'responsibility' => 'Release metadata, readiness docs, deployment recipes, wiki source, and schema snapshots.',
            'required_paths' => [
                'readme.md',
                'CHANGELOG.md',
                'RELEASE.md',
                'composer.json',
                '.env.example',
                'Docs/FrameworkStatus.md',
                'Docs/ReleaseReadinessPlan.md',
                'Docs/FrameworkWideLayerEvaluation.md',
                'Docs/PresentationLayerEvaluation.md',
                'Docs/Wiki',
                'Data/README.md',
            ],
        ],
    ];

    private string $basePath;

    public function __construct(
        private readonly FileManager $files,
        ?string $basePath = null
    ) {
        $this->basePath = rtrim($basePath ?? $this->frameworkBasePath(), DIRECTORY_SEPARATOR);
    }

    public function inspect(): array
    {
        $layers = $this->layers();
        $missing = $this->missingRequiredPaths();
        $errors = [];

        foreach ($missing as $layer => $paths) {
            if ($paths !== []) {
                $label = (string) ($layers[$layer]['label'] ?? $layer);
                $errors[] = sprintf('Framework layer [%s] is missing required paths: %s', $label, implode(', ', $paths));
            }
        }

        return [
            'ok' => $errors === [],
            'layer_count' => $this->countElements($layers),
            'required_layer_keys' => array_keys(self::LAYERS),
            'missing_required_paths' => $missing,
            'layers' => $layers,
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    public function layers(): array
    {
        $layers = [];

        foreach (self::LAYERS as $key => $definition) {
            $required = array_values(array_map('strval', (array) ($definition['required_paths'] ?? [])));
            $present = [];
            $missing = [];

            foreach ($required as $path) {
                if ($this->pathExists($path)) {
                    $present[] = $path;
                    continue;
                }

                $missing[] = $path;
            }

            $layers[$key] = [
                'key' => $key,
                'label' => (string) ($definition['label'] ?? $key),
                'responsibility' => (string) ($definition['responsibility'] ?? ''),
                'ok' => $missing === [],
                'required_paths' => $required,
                'present_paths' => $present,
                'missing_paths' => $missing,
                'php_file_count' => $this->phpFileCount($required),
            ];
        }

        return $layers;
    }

    public function missingRequiredPaths(): array
    {
        $missing = [];

        foreach ($this->layers() as $key => $layer) {
            $paths = array_values(array_map('strval', (array) ($layer['missing_paths'] ?? [])));

            if ($paths !== []) {
                $missing[$key] = $paths;
            }
        }

        return $missing;
    }

    private function pathExists(string $relative): bool
    {
        $path = $this->path($relative);

        return $this->files->fileExists($path) || $this->files->isDirectory($path);
    }

    private function phpFileCount(array $paths): int
    {
        $seen = [];

        foreach ($paths as $relative) {
            $path = $this->path((string) $relative);

            if ($this->files->fileExists($path) && str_ends_with($path, '.php')) {
                $seen[$this->files->normalizePath($this->files->getRealPath($path) ?? $path)] = true;
                continue;
            }

            if (!$this->files->isDirectory($path)) {
                continue;
            }

            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

            foreach ($iterator as $entry) {
                if ($entry instanceof SplFileInfo && $entry->isFile() && $entry->getExtension() === 'php') {
                    $seen[$this->files->normalizePath($entry->getRealPath() ?: $entry->getPathname())] = true;
                }
            }
        }

        return $this->countElements($seen);
    }

    private function path(string $relative): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . ltrim($relative, '\\/');
    }
}
