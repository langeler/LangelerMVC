<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Support;

use App\Contracts\Support\ArchitectureAlignmentManagerInterface;
use App\Utilities\Managers\FileManager;
use App\Utilities\Traits\ApplicationPathTrait;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\TypeCheckerTrait;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

class ArchitectureAlignmentManager implements ArchitectureAlignmentManagerInterface
{
    use ApplicationPathTrait;
    use ArrayTrait, CheckerTrait, ManipulationTrait, TypeCheckerTrait;

    /**
     * @var list<string>
     */
    private const FIRST_PARTY_MODULES = [
        'WebModule',
        'UserModule',
        'AdminModule',
        'ShopModule',
        'CartModule',
        'OrderModule',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_MODULE_DIRECTORIES = [
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

    /**
     * @var list<string>
     */
    private const REQUIRED_ROOT_PATHS = [
        'App',
        'Config',
        'Data',
        'Docs',
        'Public',
        'Scripts',
        'Services/README.md',
        'Storage/Logs/README.md',
        'Storage/Secure/README.md',
        'Storage/Sessions/README.md',
        'Storage/Uploads/README.md',
        'Tests',
        '.env.example',
        '.github/workflows/php.yml',
        '.gitignore',
        'CHANGELOG.md',
        'CONTRIBUTING.md',
        'RELEASE.md',
        'SECURITY.md',
        'composer.json',
        'composer.lock',
        'docker-compose.verify.yml',
        'phpunit.xml',
        'phpunit.db-matrix.xml',
        'readme.md',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_APP_DIRECTORIES = [
        'Abstracts',
        'Console',
        'Contracts',
        'Core',
        'Drivers',
        'Exceptions',
        'Framework',
        'Installer',
        'Modules',
        'Providers',
        'Resources',
        'Support',
        'Templates',
        'Utilities',
    ];

    /**
     * @var list<string>
     */
    private const CANONICAL_MANAGER_SUBLAYERS = [
        'Async',
        'Commerce',
        'Data',
        'Presentation',
        'Security',
        'Support',
        'System',
    ];

    /**
     * @var array<string, list<string>>
     */
    private const DRIVER_SUFFIXES = [
        'Caching' => ['Cache'],
        'Cryptography' => ['Crypto'],
        'Notifications' => ['NotificationChannel'],
        'Passkeys' => ['PasskeyDriver'],
        'Payments' => ['PaymentDriver'],
        'Queue' => ['QueueDriver'],
        'Session' => ['SessionDriver'],
        'Shipping' => ['CarrierAdapter'],
    ];

    /**
     * @var array<string, list<string>>
     */
    private const MODULE_DIRECTORY_SUFFIXES = [
        'Controllers' => ['Controller'],
        'Middlewares' => ['Middleware'],
        'Migrations' => [],
        'Models' => [],
        'Presenters' => ['Presenter', 'Resource'],
        'Repositories' => ['Repository'],
        'Requests' => ['Request'],
        'Responses' => ['Response'],
        'Seeds' => ['Seed'],
        'Services' => ['Service'],
        'Views' => ['View'],
        'Listeners' => ['Listener'],
        'Notifications' => ['Notification'],
    ];

    /**
     * @var array<string, list<string>>
     */
    private const MANAGER_SUBLAYER_SPECIAL_NAMES = [
        'Async' => ['DatabaseFailedJobStore', 'EventDispatcher'],
        'Commerce' => ['CommerceTotalsCalculator'],
        'Presentation' => ['TemplateEngine'],
        'Security' => ['DatabaseUserProvider', 'Gate', 'PasswordBroker', 'PermissionRegistry', 'PolicyResolver', 'SessionGuard'],
        'Support' => ['AuditLogger', 'FrameworkDoctor'],
    ];

    /**
     * @var list<string>
     */
    private const CANONICAL_SUPPORT_FILES = [
        'App/Support/ArrayMailable.php',
        'App/Support/Payments/PaymentFlow.php',
        'App/Support/Payments/PaymentIntent.php',
        'App/Support/Payments/PaymentMethod.php',
        'App/Support/Payments/PaymentResult.php',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_PUBLIC_PATHS = [
        'Public/.htaccess',
        'Public/index.php',
        'Public/install/index.php',
        'Public/assets/css/README.md',
        'Public/assets/css/langelermvc-theme.css',
        'Public/assets/images/README.md',
        'Public/assets/js/README.md',
        'Public/assets/js/langelermvc-theme.js',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_CONFIG_FILES = [
        'app.php',
        'auth.php',
        'cache.php',
        'commerce.php',
        'cookie.php',
        'db.php',
        'encryption.php',
        'feature.php',
        'http.php',
        'mail.php',
        'notifications.php',
        'operations.php',
        'payment.php',
        'queue.php',
        'session.php',
        'theme.php',
        'webmodule.php',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_DATA_FILES = [
        'Carts.sql',
        'Framework.sql',
        'Orders.sql',
        'Products.sql',
        'README.md',
        'Users.sql',
        'Web.sql',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_COMPOSER_SCRIPTS = [
        'test',
        'test:db-matrix',
        'test:runtime-backends',
        'ops:health',
        'ops:ready',
        'architecture:check',
        'release:check',
        'verify:platform',
        'verify:release',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_COMPOSE_SERVICES = [
        'mysql',
        'pgsql',
        'sqlsrv',
        'redis',
        'memcached',
    ];

    /**
     * @var array<string, class-string>
     */
    private const ROOT_MANAGER_ALIASES = [
        'App/Utilities/Managers/CacheManager.php' => \App\Utilities\Managers\Data\CacheManager::class,
        'App/Utilities/Managers/CompressionManager.php' => \App\Utilities\Managers\System\CompressionManager::class,
        'App/Utilities/Managers/DateTimeManager.php' => \App\Utilities\Managers\System\DateTimeManager::class,
        'App/Utilities/Managers/FileManager.php' => \App\Utilities\Managers\System\FileManager::class,
        'App/Utilities/Managers/IteratorManager.php' => \App\Utilities\Managers\System\IteratorManager::class,
        'App/Utilities/Managers/ReflectionManager.php' => \App\Utilities\Managers\System\ReflectionManager::class,
        'App/Utilities/Managers/SessionManager.php' => \App\Utilities\Managers\Data\SessionManager::class,
        'App/Utilities/Managers/SettingsManager.php' => \App\Utilities\Managers\System\SettingsManager::class,
    ];

    /**
     * @var array<string, class-string>
     */
    private const COMPATIBILITY_ALIASES = [
        'App/Core/ModuleManager.php' => \App\Utilities\Managers\Data\ModuleManager::class,
        'App/Support/Commerce/CartPricingManager.php' => \App\Utilities\Managers\Commerce\CartPricingManager::class,
        'App/Support/Commerce/CatalogLifecycleManager.php' => \App\Utilities\Managers\Commerce\CatalogLifecycleManager::class,
        'App/Support/Commerce/CommerceTotalsCalculator.php' => \App\Utilities\Managers\Commerce\CommerceTotalsCalculator::class,
        'App/Support/Commerce/EntitlementManager.php' => \App\Utilities\Managers\Commerce\EntitlementManager::class,
        'App/Support/Commerce/InventoryManager.php' => \App\Utilities\Managers\Commerce\InventoryManager::class,
        'App/Support/Commerce/OrderDocumentManager.php' => \App\Utilities\Managers\Commerce\OrderDocumentManager::class,
        'App/Support/Commerce/OrderLifecycleManager.php' => \App\Utilities\Managers\Commerce\OrderLifecycleManager::class,
        'App/Support/Commerce/OrderReturnManager.php' => \App\Utilities\Managers\Commerce\OrderReturnManager::class,
        'App/Support/Commerce/PromotionManager.php' => \App\Utilities\Managers\Commerce\PromotionManager::class,
        'App/Support/Commerce/ShippingManager.php' => \App\Utilities\Managers\Commerce\ShippingManager::class,
        'App/Support/Commerce/SubscriptionManager.php' => \App\Utilities\Managers\Commerce\SubscriptionManager::class,
        'App/Support/Theming/ThemeManager.php' => \App\Utilities\Managers\Presentation\ThemeManager::class,
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_VIDE_DIRECTIVES = [
        'section',
        'endsection',
        'yield',
        'push',
        'endpush',
        'stack',
        'hasSection',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_VIEW_COMPOSITION_METHODS = [
        'startSection',
        'stopSection',
        'yieldContent',
        'hasSection',
        'push',
        'stopPush',
        'stack',
    ];

    /**
     * @var list<string>
     */
    private const HISTORICAL_DOCS = [
        'Docs/IteratorManager Usage.pdf',
        'Docs/IteratorManager Usage.rtf',
        'Docs/abstractcryptoclass.rtf',
        'Docs/opensslcryptoclass.rtf',
        'Docs/sodiumcryptoclass.rtf',
        'Docs/Untitled 5.rtf',
        'Docs/Untitled 6.rtf',
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
        $checks = [
            'repository_contract' => $this->repositoryContractCheck(),
            'app_layer_boundaries' => $this->appLayerBoundaryCheck(),
            'class_placement' => $this->classPlacementCheck(),
            'public_bootstrap' => $this->publicBootstrapCheck(),
            'config_data_release' => $this->configDataReleaseCheck(),
            'tests_ci_scripts' => $this->testsCiScriptsCheck(),
            'strict_types' => $this->strictTypesCheck(),
            'manager_placement' => $this->managerPlacementCheck(),
            'module_contracts' => $this->moduleContractCheck(),
            'presentation_native_surface' => $this->presentationNativeSurfaceCheck(),
            'documentation_alignment' => $this->documentationAlignmentCheck(),
        ];

        $errors = [];
        $warnings = [];

        foreach ($checks as $check) {
            $errors = array_merge($errors, array_map('strval', (array) ($check['errors'] ?? [])));
            $warnings = array_merge($warnings, array_map('strval', (array) ($check['warnings'] ?? [])));
        }

        $errors = array_values(array_unique($errors));
        $warnings = array_values(array_unique($warnings));

        return [
            'ok' => $errors === [],
            'check_count' => $this->countElements($checks),
            'rules' => $this->rules(),
            'checks' => $checks,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    public function rules(): array
    {
        return [
            'repository_contract' => [
                'label' => 'Repository Contract',
                'description' => 'Release-critical root files, reserved folders, storage sentinels, and ignore rules remain present.',
            ],
            'app_layer_boundaries' => [
                'label' => 'App Layer Boundaries',
                'description' => 'The App tree keeps the expected top-level framework folders and avoids stray class-bearing files outside the layer map.',
            ],
            'class_placement' => [
                'label' => 'Class Placement',
                'description' => 'Every class-bearing App file keeps path, namespace, symbol name, layer role, module suffix, support object, manager sublayer, and alias-corridor placement aligned.',
            ],
            'public_bootstrap' => [
                'label' => 'Public / Bootstrap Thinness',
                'description' => 'Public and bootstrap entrypoints stay thin and delegate into Bootstrap, CoreProvider, and the installer/view layers.',
            ],
            'config_data_release' => [
                'label' => 'Config / Data / Release Parity',
                'description' => 'Tracked config files, environment template, SQL snapshots, compose matrix, and release docs stay aligned.',
            ],
            'tests_ci_scripts' => [
                'label' => 'Tests / CI / Scripts',
                'description' => 'PHPUnit suites, GitHub Actions, Composer scripts, and maintenance scripts stay available for release validation.',
            ],
            'strict_types' => [
                'label' => 'Strict Class Files',
                'description' => 'Class-bearing App PHP files declare strict types for predictable PHP 8.4 behavior.',
            ],
            'manager_placement' => [
                'label' => 'Canonical Managers',
                'description' => 'Concrete manager-layer implementations live under approved App/Utilities/Managers/* sublayers, with legacy paths kept as thin aliases only.',
            ],
            'module_contracts' => [
                'label' => 'Documented Module Shape',
                'description' => 'First-party modules keep the repeated MVC/module directory contract and document intentionally empty surfaces.',
            ],
            'presentation_native_surface' => [
                'label' => 'Native Presentation Surface',
                'description' => 'The .vide engine, view composition API, layouts, components, resources, and assets remain present and aligned.',
            ],
            'documentation_alignment' => [
                'label' => 'Documentation Alignment',
                'description' => 'Current docs describe the canonical architecture, while historical artifacts are explicitly labeled archival.',
            ],
        ];
    }

    public function violations(): array
    {
        $payload = $this->inspect();

        return [
            'errors' => array_values(array_map('strval', (array) ($payload['errors'] ?? []))),
            'warnings' => array_values(array_map('strval', (array) ($payload['warnings'] ?? []))),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function repositoryContractCheck(): array
    {
        $missingPaths = array_values(array_filter(self::REQUIRED_ROOT_PATHS, fn(string $path): bool => !$this->pathExists($path)));
        $gitignore = $this->read('.gitignore');
        $requiredIgnorePatterns = [
            '.env',
            '/vendor/',
            '/Storage/Cache/',
            '/Storage/Logs/*',
            '/Storage/Secure/*',
            '/Storage/Uploads/*',
            '!/Storage/Logs/README.md',
            '!/Storage/Secure/README.md',
            '!/Storage/Sessions/README.md',
            '!/Storage/Uploads/README.md',
        ];
        $missingIgnorePatterns = [];

        foreach ($requiredIgnorePatterns as $pattern) {
            if (!$this->contains($gitignore, $pattern)) {
                $missingIgnorePatterns[] = $pattern;
            }
        }

        $errors = array_values(array_filter([
            $missingPaths === [] ? null : 'Missing repository contract paths: ' . implode(', ', $missingPaths),
            $missingIgnorePatterns === [] ? null : 'Missing .gitignore release/runtime patterns: ' . implode(', ', $missingIgnorePatterns),
        ]));

        return [
            'ok' => $errors === [],
            'required_paths' => self::REQUIRED_ROOT_PATHS,
            'missing_paths' => $missingPaths,
            'required_ignore_patterns' => $requiredIgnorePatterns,
            'missing_ignore_patterns' => $missingIgnorePatterns,
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function appLayerBoundaryCheck(): array
    {
        $missingDirectories = array_values(array_filter(
            self::REQUIRED_APP_DIRECTORIES,
            fn(string $directory): bool => !$this->pathExists('App/' . $directory)
        ));
        $allowed = array_fill_keys(self::REQUIRED_APP_DIRECTORIES, true);
        $unexpectedDirectories = [];

        foreach ($this->childDirectories('App') as $directory) {
            if (!isset($allowed[$directory])) {
                $unexpectedDirectories[] = $directory;
            }
        }

        $rootPhpFiles = [];
        $appRoot = $this->path('App');

        if ($this->files->isDirectory($appRoot)) {
            foreach (glob($appRoot . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
                if (is_file($file)) {
                    $rootPhpFiles[] = $this->relativePath($file);
                }
            }
        }

        $errors = array_values(array_filter([
            $missingDirectories === [] ? null : 'Missing App layer directories: ' . implode(', ', $missingDirectories),
            $unexpectedDirectories === [] ? null : 'Unexpected App top-level directories: ' . implode(', ', $unexpectedDirectories),
            $rootPhpFiles === [] ? null : 'App root should not contain direct PHP files: ' . implode(', ', $rootPhpFiles),
        ]));

        return [
            'ok' => $errors === [],
            'required_directories' => self::REQUIRED_APP_DIRECTORIES,
            'missing_directories' => $missingDirectories,
            'unexpected_directories' => $unexpectedDirectories,
            'root_php_files' => $rootPhpFiles,
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function classPlacementCheck(): array
    {
        $errors = [];
        $classes = [];
        $layers = [];
        $compatibilityAliases = [];

        foreach ($this->phpFiles('App') as $file) {
            $relative = $this->relativePath($file);

            if ($this->isNonClassSurface($relative)) {
                continue;
            }

            $contents = $this->read($relative);

            if (!$this->isClassBearingFile($contents)) {
                continue;
            }

            $metadata = $this->classMetadata($contents);

            if ($metadata === null) {
                $errors[] = sprintf('%s is class-bearing but could not be parsed for namespace and symbol placement.', $relative);
                continue;
            }

            $layer = $this->pathSegment($relative, 1);
            $layers[$layer] = ($layers[$layer] ?? 0) + 1;
            $classes[] = $relative;

            if ($this->isKnownCompatibilityAlias($relative)) {
                $compatibilityAliases[] = $relative;
            }

            $expectedNamespace = str_replace('/', '\\', dirname($relative));
            $expectedName = pathinfo($relative, PATHINFO_FILENAME);

            if ($metadata['namespace'] !== $expectedNamespace) {
                $errors[] = sprintf(
                    '%s uses namespace [%s] instead of path namespace [%s].',
                    $relative,
                    $metadata['namespace'],
                    $expectedNamespace
                );
            }

            if ($metadata['name'] !== $expectedName) {
                $errors[] = sprintf(
                    '%s declares %s [%s] instead of path symbol [%s].',
                    $relative,
                    $metadata['kind'],
                    $metadata['name'],
                    $expectedName
                );
            }

            $errors = array_merge($errors, $this->placementErrors($relative, $metadata));
        }

        ksort($layers);
        sort($compatibilityAliases);

        return [
            'ok' => $errors === [],
            'class_count' => $this->countElements($classes),
            'layers' => $layers,
            'canonical_manager_sublayers' => self::CANONICAL_MANAGER_SUBLAYERS,
            'compatibility_alias_count' => $this->countElements($compatibilityAliases),
            'compatibility_aliases' => $compatibilityAliases,
            'errors' => array_values(array_unique($errors)),
            'warnings' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function publicBootstrapCheck(): array
    {
        $missingPaths = array_values(array_filter(self::REQUIRED_PUBLIC_PATHS, fn(string $path): bool => !$this->pathExists($path)));
        $publicIndex = $this->read('Public/index.php');
        $bootstrapApp = $this->read('bootstrap/app.php');
        $bootstrapConsole = $this->read('bootstrap/console.php');
        $console = $this->read('console');
        $installer = $this->read('Public/install/index.php');
        $errors = [];

        if (!$this->contains($publicIndex, '/bootstrap/app.php') || !$this->contains($publicIndex, '->run()')) {
            $errors[] = 'Public/index.php should stay a thin bootstrap/app.php front controller.';
        }

        if (substr_count($publicIndex, ';') > 3) {
            $errors[] = 'Public/index.php contains more executable statements than expected for the thin front controller.';
        }

        if (!$this->contains($bootstrapApp, 'new Bootstrap') || !$this->contains($bootstrapApp, 'createApplication')) {
            $errors[] = 'bootstrap/app.php should delegate application creation to App\\Core\\Bootstrap.';
        }

        if (!$this->contains($bootstrapConsole, 'new Bootstrap') || !$this->contains($bootstrapConsole, 'createConsoleKernel')) {
            $errors[] = 'bootstrap/console.php should delegate console creation to App\\Core\\Bootstrap.';
        }

        if (!$this->startsWith($console, '#!/usr/bin/env php') || !$this->contains($console, 'bootstrap/console.php')) {
            $errors[] = 'console should stay the thin CLI entrypoint into bootstrap/console.php.';
        }

        foreach (['InstallerWizard', 'InstallerView', 'HttpSecurityManager'] as $symbol) {
            if (!$this->contains($installer, $symbol)) {
                $errors[] = sprintf('Public/install/index.php should integrate %s.', $symbol);
            }
        }

        if ($missingPaths !== []) {
            array_unshift($errors, 'Missing public/bootstrap paths: ' . implode(', ', $missingPaths));
        }

        return [
            'ok' => $errors === [],
            'required_paths' => self::REQUIRED_PUBLIC_PATHS,
            'missing_paths' => $missingPaths,
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function configDataReleaseCheck(): array
    {
        $missingConfig = array_values(array_filter(
            self::REQUIRED_CONFIG_FILES,
            fn(string $file): bool => !$this->pathExists('Config/' . $file)
        ));
        $missingData = array_values(array_filter(
            self::REQUIRED_DATA_FILES,
            fn(string $file): bool => !$this->pathExists('Data/' . $file)
        ));
        $env = $this->read('.env.example');
        $missingEnvAnchors = [];

        foreach (['APP_NAME=', 'DB_CONNECTION=', 'PAYMENT_DRIVER=', 'COMMERCE_CURRENCY=', 'THEME_DEFAULT='] as $anchor) {
            if (!$this->contains($env, $anchor)) {
                $missingEnvAnchors[] = $anchor;
            }
        }

        $dataReadme = $this->read('Data/README.md');
        $missingDataReadmeAnchors = [];

        foreach (self::REQUIRED_DATA_FILES as $file) {
            if ($file !== 'README.md' && !$this->contains($dataReadme, $file)) {
                $missingDataReadmeAnchors[] = $file;
            }
        }

        $errors = array_values(array_filter([
            $missingConfig === [] ? null : 'Missing tracked Config files: ' . implode(', ', $missingConfig),
            $missingData === [] ? null : 'Missing tracked Data files: ' . implode(', ', $missingData),
            $missingEnvAnchors === [] ? null : 'Missing .env.example architecture anchors: ' . implode(', ', $missingEnvAnchors),
            $missingDataReadmeAnchors === [] ? null : 'Data/README.md does not list SQL snapshots: ' . implode(', ', $missingDataReadmeAnchors),
        ]));

        return [
            'ok' => $errors === [],
            'required_config_files' => self::REQUIRED_CONFIG_FILES,
            'missing_config_files' => $missingConfig,
            'required_data_files' => self::REQUIRED_DATA_FILES,
            'missing_data_files' => $missingData,
            'missing_env_anchors' => $missingEnvAnchors,
            'missing_data_readme_anchors' => $missingDataReadmeAnchors,
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function testsCiScriptsCheck(): array
    {
        $composer = json_decode($this->read('composer.json'), true);
        $scripts = is_array($composer) && is_array($composer['scripts'] ?? null) ? $composer['scripts'] : [];
        $missingScripts = array_values(array_filter(
            self::REQUIRED_COMPOSER_SCRIPTS,
            fn(string $script): bool => !array_key_exists($script, $scripts)
        ));
        $workflow = $this->read('.github/workflows/php.yml');
        $missingWorkflowAnchors = [];

        foreach (['composer validate --no-check-publish', 'composer test', 'composer test:${{ matrix.target }}'] as $anchor) {
            if (!$this->contains($workflow, $anchor)) {
                $missingWorkflowAnchors[] = $anchor;
            }
        }

        $compose = $this->read('docker-compose.verify.yml');
        $missingComposeServices = [];

        foreach (self::REQUIRED_COMPOSE_SERVICES as $service) {
            if (!preg_match('/^\s{2}' . preg_quote($service, '/') . ':\s*$/m', $compose)) {
                $missingComposeServices[] = $service;
            }
        }

        $missingTestPaths = array_values(array_filter([
            'Tests/Framework',
            'Tests/DbMatrix',
            'Tests/Unit/README.md',
            'Tests/Integration/README.md',
            'Scripts/AuditNativeToTraitConsistency.pl',
            'Scripts/GenerateUtilitiesTraitsReference.pl',
        ], fn(string $path): bool => !$this->pathExists($path)));

        $errors = array_values(array_filter([
            $missingScripts === [] ? null : 'Missing Composer release/verification scripts: ' . implode(', ', $missingScripts),
            $missingWorkflowAnchors === [] ? null : 'GitHub Actions workflow missing verification anchors: ' . implode(', ', $missingWorkflowAnchors),
            $missingComposeServices === [] ? null : 'docker-compose.verify.yml missing services: ' . implode(', ', $missingComposeServices),
            $missingTestPaths === [] ? null : 'Missing test/script architecture paths: ' . implode(', ', $missingTestPaths),
        ]));

        return [
            'ok' => $errors === [],
            'required_composer_scripts' => self::REQUIRED_COMPOSER_SCRIPTS,
            'missing_composer_scripts' => $missingScripts,
            'required_compose_services' => self::REQUIRED_COMPOSE_SERVICES,
            'missing_compose_services' => $missingComposeServices,
            'missing_workflow_anchors' => $missingWorkflowAnchors,
            'missing_test_paths' => $missingTestPaths,
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function strictTypesCheck(): array
    {
        $classFiles = [];
        $missing = [];

        foreach ($this->phpFiles('App') as $file) {
            $relative = $this->relativePath($file);
            $contents = $this->read($relative);

            if (!$this->isClassBearingFile($contents)) {
                continue;
            }

            $classFiles[] = $relative;

            if (!$this->hasStrictTypes($contents)) {
                $missing[] = $relative;
            }
        }

        return [
            'ok' => $missing === [],
            'class_file_count' => $this->countElements($classFiles),
            'missing' => $missing,
            'errors' => $missing === [] ? [] : ['Class-bearing App files missing strict types: ' . implode(', ', $missing)],
            'warnings' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function managerPlacementCheck(): array
    {
        $managerFiles = array_values(array_filter(
            $this->phpFiles('App'),
            function (string $file): bool {
                $relative = $this->relativePath($file);

                return $this->startsWith($relative, 'App/Utilities/Managers/')
                    || $this->endsWith($relative, 'Manager.php')
                    || isset(self::COMPATIBILITY_ALIASES[$relative])
                    || isset(self::ROOT_MANAGER_ALIASES[$relative]);
            }
        ));
        $canonical = [];
        $compatibility = [];
        $misplaced = [];
        $aliasErrors = [];

        foreach ($managerFiles as $file) {
            $relative = $this->relativePath($file);

            if (isset(self::COMPATIBILITY_ALIASES[$relative])) {
                $compatibility[] = $relative;

                if (!$this->isThinAlias($relative, self::COMPATIBILITY_ALIASES[$relative])) {
                    $aliasErrors[] = sprintf('%s is not a thin compatibility alias for %s.', $relative, self::COMPATIBILITY_ALIASES[$relative]);
                }

                continue;
            }

            if (isset(self::ROOT_MANAGER_ALIASES[$relative])) {
                $compatibility[] = $relative;

                if (!$this->isThinAlias($relative, self::ROOT_MANAGER_ALIASES[$relative])) {
                    $aliasErrors[] = sprintf('%s should stay a thin root manager alias for %s.', $relative, self::ROOT_MANAGER_ALIASES[$relative]);
                }

                continue;
            }

            if (
                preg_match('#^App/Utilities/Managers/([^/]+)/.+\.php$#', $relative, $matches) === 1
                && $this->isInArray((string) $matches[1], self::CANONICAL_MANAGER_SUBLAYERS, true)
            ) {
                $canonical[] = $relative;
                continue;
            }

            $misplaced[] = $relative;
        }

        foreach (self::ROOT_MANAGER_ALIASES as $relative => $target) {
            if ($this->pathExists($relative) && !$this->isThinAlias($relative, $target)) {
                $aliasErrors[] = sprintf('%s should stay a thin root manager alias for %s.', $relative, $target);
            }
        }

        foreach (self::COMPATIBILITY_ALIASES as $relative => $target) {
            if (!$this->pathExists($relative)) {
                $aliasErrors[] = sprintf('Missing compatibility alias %s for %s.', $relative, $target);
            }
        }

        $errors = array_merge(
            $misplaced === [] ? [] : ['Manager classes outside the canonical/compatibility surfaces: ' . implode(', ', $misplaced)],
            $aliasErrors
        );

        return [
            'ok' => $errors === [],
            'canonical_count' => $this->countElements($canonical),
            'compatibility_alias_count' => $this->countElements($compatibility),
            'root_alias_count' => $this->countElements(self::ROOT_MANAGER_ALIASES),
            'canonical_sublayers' => self::CANONICAL_MANAGER_SUBLAYERS,
            'canonical' => $canonical,
            'compatibility_aliases' => $compatibility,
            'misplaced' => $misplaced,
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function moduleContractCheck(): array
    {
        $modules = [];
        $errors = [];

        foreach (self::FIRST_PARTY_MODULES as $module) {
            $moduleRoot = 'App/Modules/' . $module;
            $moduleErrors = [];
            $directories = [];

            if (!$this->pathExists($moduleRoot)) {
                $errors[] = sprintf('Missing first-party module root [%s].', $moduleRoot);
                $modules[$module] = ['present' => false, 'directories' => []];
                continue;
            }

            foreach (self::REQUIRED_MODULE_DIRECTORIES as $directory) {
                $relative = $moduleRoot . '/' . $directory;
                $readme = $relative . '/README.md';
                $phpFiles = $this->phpFiles($relative);
                $exists = $this->pathExists($relative);
                $documented = $this->pathExists($readme);

                if (!$exists) {
                    $moduleErrors[] = sprintf('%s is missing required directory [%s].', $module, $directory);
                }

                if ($exists && !$documented) {
                    $moduleErrors[] = sprintf('%s directory [%s] is missing README.md.', $module, $directory);
                }

                if ($exists && $phpFiles === [] && !$documented) {
                    $moduleErrors[] = sprintf('%s directory [%s] has no PHP files and no README explanation.', $module, $directory);
                }

                $directories[$directory] = [
                    'present' => $exists,
                    'documented' => $documented,
                    'php_files' => $this->countElements($phpFiles),
                ];
            }

            foreach ($this->childDirectories($moduleRoot) as $directory) {
                $relative = $moduleRoot . '/' . $directory;

                if (!$this->pathExists($relative . '/README.md')) {
                    $moduleErrors[] = sprintf('%s extension directory [%s] is missing README.md.', $module, $directory);
                }
            }

            $errors = array_merge($errors, $moduleErrors);
            $modules[$module] = [
                'present' => true,
                'directories' => $directories,
                'errors' => $moduleErrors,
            ];
        }

        return [
            'ok' => $errors === [],
            'modules' => $modules,
            'required_directories' => self::REQUIRED_MODULE_DIRECTORIES,
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentationNativeSurfaceCheck(): array
    {
        $requiredPaths = [
            'App/Abstracts/Presentation/View.php',
            'App/Contracts/Presentation/ViewInterface.php',
            'App/Contracts/Presentation/TemplateEngineInterface.php',
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
        ];
        $missingPaths = array_values(array_filter($requiredPaths, fn(string $path): bool => !$this->pathExists($path)));
        $templateEngine = $this->read('App/Utilities/Managers/Presentation/TemplateEngine.php');
        $viewInterface = $this->read('App/Contracts/Presentation/ViewInterface.php');
        $missingDirectives = [];
        $missingMethods = [];
        $rawPhpVideFiles = [];

        foreach (self::REQUIRED_VIDE_DIRECTIVES as $directive) {
            if (
                !$this->contains($templateEngine, "'" . $directive . "'")
                && !$this->contains($templateEngine, '@' . $directive)
                && !$this->contains($templateEngine, '\\@' . $directive)
            ) {
                $missingDirectives[] = $directive;
            }
        }

        foreach (self::REQUIRED_VIEW_COMPOSITION_METHODS as $method) {
            if (!preg_match('/function\s+' . preg_quote($method, '/') . '\s*\(/', $viewInterface)) {
                $missingMethods[] = $method;
            }
        }

        foreach ($this->filesWithExtension('App/Templates', 'vide') as $file) {
            $relative = $this->relativePath($file);

            if ($this->contains($this->read($relative), '<?php')) {
                $rawPhpVideFiles[] = $relative;
            }
        }

        $errors = array_values(array_filter([
            $missingPaths === [] ? null : 'Missing native presentation paths: ' . implode(', ', $missingPaths),
            $missingDirectives === [] ? null : 'Missing .vide composition directives: ' . implode(', ', $missingDirectives),
            $missingMethods === [] ? null : 'Missing view composition methods: ' . implode(', ', $missingMethods),
            $rawPhpVideFiles === [] ? null : 'Native .vide templates contain raw PHP tags: ' . implode(', ', $rawPhpVideFiles),
        ]));

        return [
            'ok' => $errors === [],
            'required_paths' => $requiredPaths,
            'missing_paths' => $missingPaths,
            'required_directives' => self::REQUIRED_VIDE_DIRECTIVES,
            'missing_directives' => $missingDirectives,
            'required_view_methods' => self::REQUIRED_VIEW_COMPOSITION_METHODS,
            'missing_view_methods' => $missingMethods,
            'native_template_count' => $this->countElements($this->filesWithExtension('App/Templates', 'vide')),
            'raw_php_vide_files' => $rawPhpVideFiles,
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function documentationAlignmentCheck(): array
    {
        $requiredDocs = [
            'readme.md' => ['framework:architecture', 'ArchitectureAlignment.md', 'repository contract', 'class placement'],
            'Docs/README.md' => ['ArchitectureAlignment.md', 'Historical / Archival Files'],
            'Docs/ArchitectureOverview.md' => ['framework:architecture', 'class placement'],
            'Docs/FrameworkWideLayerEvaluation.md' => ['architecture-alignment manager', 'class placement'],
            'Docs/ArchitectureAlignment.md' => ['Architecture Alignment', 'Repository Contract', 'Class Placement', 'Support Surface And Alias Corridors', 'Public / Bootstrap', 'Config / Data / Release Parity', 'Tests / CI / Scripts'],
            'Docs/FolderStructure.md' => ['ArchitectureAlignment.md', 'class placement'],
            'Docs/OperationsGuide.md' => ['framework:architecture', 'class placement'],
        ];
        $errors = [];
        $historical = [];

        foreach ($requiredDocs as $doc => $phrases) {
            $contents = $this->read($doc);

            if ($contents === '') {
                $errors[] = sprintf('Missing architecture documentation [%s].', $doc);
                continue;
            }

            foreach ($phrases as $phrase) {
                if (!$this->contains($contents, $phrase)) {
                    $errors[] = sprintf('Architecture documentation [%s] does not mention [%s].', $doc, $phrase);
                }
            }
        }

        $docsReadme = $this->read('Docs/README.md');

        foreach (self::HISTORICAL_DOCS as $doc) {
            if ($this->pathExists($doc)) {
                $historical[] = $doc;
            }

            if ($this->pathExists($doc) && !$this->contains($docsReadme, basename($doc))) {
                $errors[] = sprintf('Historical documentation artifact [%s] is not listed in Docs/README.md.', $doc);
            }
        }

        return [
            'ok' => $errors === [],
            'required_docs' => array_keys($requiredDocs),
            'historical_artifacts' => $historical,
            'errors' => $errors,
            'warnings' => [],
        ];
    }

    /**
     * @param array{namespace:string,kind:string,name:string} $metadata
     * @return list<string>
     */
    private function placementErrors(string $relative, array $metadata): array
    {
        $layer = $this->pathSegment($relative, 1);

        return match ($layer) {
            'Abstracts' => $metadata['kind'] === 'interface'
                ? [sprintf('%s is an interface inside App/Abstracts; interfaces belong in App/Contracts.', $relative)]
                : [],
            'Console' => $this->consolePlacementErrors($relative, $metadata),
            'Contracts' => $this->contractsPlacementErrors($relative, $metadata),
            'Core' => $this->corePlacementErrors($relative),
            'Drivers' => $this->driverPlacementErrors($relative, $metadata),
            'Exceptions' => $this->suffixPlacementErrors($relative, $metadata['name'], ['Exception'], 'exception classes'),
            'Framework' => $this->frameworkPlacementErrors($relative),
            'Installer' => $this->prefixPlacementErrors($relative, $metadata['name'], ['Installer'], 'installer classes'),
            'Modules' => $this->modulePlacementErrors($relative, $metadata),
            'Providers' => $this->suffixPlacementErrors($relative, $metadata['name'], ['Provider'], 'provider classes'),
            'Resources' => [sprintf('%s is a class-bearing PHP file inside App/Resources; resources should be static source assets.', $relative)],
            'Support' => $this->supportPlacementErrors($relative, $metadata),
            'Templates' => [sprintf('%s is a class-bearing PHP file inside App/Templates; shared presentation code belongs in App/Utilities/Managers/Presentation or module Views.', $relative)],
            'Utilities' => $this->utilityPlacementErrors($relative, $metadata),
            default => [sprintf('%s lives under unknown App layer [%s].', $relative, $layer)],
        };
    }

    /**
     * @param array{namespace:string,kind:string,name:string} $metadata
     * @return list<string>
     */
    private function consolePlacementErrors(string $relative, array $metadata): array
    {
        if ($this->startsWith($relative, 'App/Console/Commands/')) {
            return $this->suffixPlacementErrors($relative, $metadata['name'], ['Command'], 'console commands');
        }

        return $relative === 'App/Console/ConsoleKernel.php'
            ? []
            : [sprintf('%s is outside the known ConsoleKernel or Commands surfaces.', $relative)];
    }

    /**
     * @param array{namespace:string,kind:string,name:string} $metadata
     * @return list<string>
     */
    private function contractsPlacementErrors(string $relative, array $metadata): array
    {
        $errors = [];

        if ($metadata['kind'] !== 'interface') {
            $errors[] = sprintf('%s declares a %s inside App/Contracts; contracts must be interfaces.', $relative, $metadata['kind']);
        }

        return array_merge(
            $errors,
            $this->suffixPlacementErrors($relative, $metadata['name'], ['Interface'], 'contract interfaces')
        );
    }

    /**
     * @return list<string>
     */
    private function corePlacementErrors(string $relative): array
    {
        if ($relative === 'App/Core/ModuleManager.php') {
            return $this->isThinAlias($relative, self::COMPATIBILITY_ALIASES[$relative] ?? '')
                ? []
                : [sprintf('%s must remain a thin compatibility alias for the canonical module manager.', $relative)];
        }

        if ($this->endsWith($relative, 'Manager.php')) {
            return [sprintf('%s is a new manager in App/Core; concrete managers belong under App/Utilities/Managers/*.', $relative)];
        }

        return [];
    }

    /**
     * @param array{namespace:string,kind:string,name:string} $metadata
     * @return list<string>
     */
    private function driverPlacementErrors(string $relative, array $metadata): array
    {
        $driverGroup = $this->pathSegment($relative, 2);

        if (!isset(self::DRIVER_SUFFIXES[$driverGroup])) {
            return [sprintf('%s lives in unknown driver group [%s].', $relative, $driverGroup)];
        }

        return $this->suffixPlacementErrors($relative, $metadata['name'], self::DRIVER_SUFFIXES[$driverGroup], 'driver adapters');
    }

    /**
     * @return list<string>
     */
    private function frameworkPlacementErrors(string $relative): array
    {
        return $this->startsWith($relative, 'App/Framework/Migrations/')
            ? []
            : [sprintf('%s is outside the known App/Framework/Migrations surface.', $relative)];
    }

    /**
     * @param array{namespace:string,kind:string,name:string} $metadata
     * @return list<string>
     */
    private function modulePlacementErrors(string $relative, array $metadata): array
    {
        $module = $this->pathSegment($relative, 2);
        $directory = $this->pathSegment($relative, 3);

        if ($module === '' || !$this->endsWith($module, 'Module')) {
            return [sprintf('%s is not inside a named *Module root.', $relative)];
        }

        if (!isset(self::MODULE_DIRECTORY_SUFFIXES[$directory])) {
            return [sprintf('%s lives in unsupported module directory [%s].', $relative, $directory)];
        }

        $suffixes = self::MODULE_DIRECTORY_SUFFIXES[$directory];

        if ($suffixes === []) {
            return [];
        }

        return $this->suffixPlacementErrors($relative, $metadata['name'], $suffixes, 'module ' . $directory);
    }

    /**
     * @param array{namespace:string,kind:string,name:string} $metadata
     * @return list<string>
     */
    private function supportPlacementErrors(string $relative, array $metadata): array
    {
        if (isset(self::COMPATIBILITY_ALIASES[$relative])) {
            return $this->isThinAlias($relative, self::COMPATIBILITY_ALIASES[$relative])
                ? []
                : [sprintf('%s must stay a thin compatibility alias for %s.', $relative, self::COMPATIBILITY_ALIASES[$relative])];
        }

        if ($this->startsWith($relative, 'App/Support/Commerce/') || $this->startsWith($relative, 'App/Support/Theming/')) {
            return [sprintf('%s is in a compatibility alias corridor but is not registered as an approved alias.', $relative)];
        }

        if ($this->isInArray($relative, self::CANONICAL_SUPPORT_FILES, true)) {
            return [];
        }

        if ($this->startsWith($relative, 'App/Support/Payments/')) {
            if (!$this->startsWith($metadata['name'], 'Payment')) {
                return [sprintf('%s is a payment support value but does not use the Payment* name family.', $relative)];
            }

            if ($this->endsWith($metadata['name'], 'Manager')) {
                return [sprintf('%s is a manager in App/Support/Payments; payment managers belong under App/Utilities/Managers/Support.', $relative)];
            }

            return [];
        }

        return [sprintf('%s is an unclassified App/Support class; add a narrow support rule or move it into Contracts, Abstracts, Utilities, Drivers, or Modules.', $relative)];
    }

    /**
     * @param array{namespace:string,kind:string,name:string} $metadata
     * @return list<string>
     */
    private function utilityPlacementErrors(string $relative, array $metadata): array
    {
        $utilityGroup = $this->pathSegment($relative, 2);

        return match ($utilityGroup) {
            'Finders' => $this->suffixPlacementErrors($relative, $metadata['name'], ['Finder'], 'finder utilities'),
            'Handlers' => $this->suffixPlacementErrors($relative, $metadata['name'], ['Handler'], 'handler utilities'),
            'Managers' => $this->utilityManagerPlacementErrors($relative, $metadata),
            'Query' => $this->suffixPlacementErrors($relative, $metadata['name'], ['Query'], 'query helpers'),
            'Sanitation' => $this->suffixPlacementErrors($relative, $metadata['name'], ['Sanitizer'], 'sanitizers'),
            'Traits' => $this->traitPlacementErrors($relative, $metadata),
            'Validation' => $this->suffixPlacementErrors($relative, $metadata['name'], ['Validator'], 'validators'),
            default => [sprintf('%s lives in unknown App/Utilities group [%s].', $relative, $utilityGroup)],
        };
    }

    /**
     * @param array{namespace:string,kind:string,name:string} $metadata
     * @return list<string>
     */
    private function utilityManagerPlacementErrors(string $relative, array $metadata): array
    {
        if (isset(self::ROOT_MANAGER_ALIASES[$relative])) {
            return $this->isThinAlias($relative, self::ROOT_MANAGER_ALIASES[$relative])
                ? []
                : [sprintf('%s must remain a thin root manager alias for %s.', $relative, self::ROOT_MANAGER_ALIASES[$relative])];
        }

        $sublayer = $this->pathSegment($relative, 3);

        if ($sublayer === '') {
            return [sprintf('%s is a concrete manager at App/Utilities/Managers root; use a canonical manager sublayer.', $relative)];
        }

        if (!$this->isInArray($sublayer, self::CANONICAL_MANAGER_SUBLAYERS, true)) {
            return [sprintf('%s uses unsupported manager sublayer [%s].', $relative, $sublayer)];
        }

        $specialNames = self::MANAGER_SUBLAYER_SPECIAL_NAMES[$sublayer] ?? [];

        if ($this->endsWith($metadata['name'], 'Manager') || $this->isInArray($metadata['name'], $specialNames, true)) {
            return [];
        }

        return [sprintf(
            '%s declares [%s] in the manager layer without a Manager suffix or approved service-role exception.',
            $relative,
            $metadata['name']
        )];
    }

    /**
     * @param array{namespace:string,kind:string,name:string} $metadata
     * @return list<string>
     */
    private function traitPlacementErrors(string $relative, array $metadata): array
    {
        $errors = [];

        if ($metadata['kind'] !== 'trait') {
            $errors[] = sprintf('%s declares a %s inside App/Utilities/Traits; this surface is for traits only.', $relative, $metadata['kind']);
        }

        return array_merge(
            $errors,
            $this->suffixPlacementErrors($relative, $metadata['name'], ['Trait'], 'utility traits')
        );
    }

    /**
     * @return list<string>
     */
    private function suffixPlacementErrors(string $relative, string $name, array $suffixes, string $surface): array
    {
        foreach ($suffixes as $suffix) {
            if ($this->endsWith($name, $suffix)) {
                return [];
            }
        }

        return [sprintf(
            '%s declares [%s], but %s must use one of these suffixes: %s.',
            $relative,
            $name,
            $surface,
            implode(', ', $suffixes)
        )];
    }

    /**
     * @return list<string>
     */
    private function prefixPlacementErrors(string $relative, string $name, array $prefixes, string $surface): array
    {
        foreach ($prefixes as $prefix) {
            if ($this->startsWith($name, $prefix)) {
                return [];
            }
        }

        return [sprintf(
            '%s declares [%s], but %s must use one of these prefixes: %s.',
            $relative,
            $name,
            $surface,
            implode(', ', $prefixes)
        )];
    }

    private function isKnownCompatibilityAlias(string $relative): bool
    {
        return isset(self::COMPATIBILITY_ALIASES[$relative]) || isset(self::ROOT_MANAGER_ALIASES[$relative]);
    }

    private function isNonClassSurface(string $relative): bool
    {
        return $this->startsWith($relative, 'App/Templates/')
            || $this->endsWith($relative, '/Routes/web.php');
    }

    /**
     * @return array{namespace:string,kind:string,name:string}|null
     */
    private function classMetadata(string $contents): ?array
    {
        if (
            preg_match('/^namespace\s+([^;]+);/m', $contents, $namespaceMatch) !== 1
            || preg_match('/^\s*(?:abstract\s+|final\s+|readonly\s+)*(class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)/m', $contents, $symbolMatch) !== 1
        ) {
            return null;
        }

        return [
            'namespace' => (string) $namespaceMatch[1],
            'kind' => (string) $symbolMatch[1],
            'name' => (string) $symbolMatch[2],
        ];
    }

    private function pathSegment(string $relative, int $index): string
    {
        $parts = explode('/', $relative);

        return (string) ($parts[$index] ?? '');
    }

    /**
     * @return list<string>
     */
    private function phpFiles(string $relativeRoot): array
    {
        return $this->filesWithExtension($relativeRoot, 'php');
    }

    /**
     * @return list<string>
     */
    private function filesWithExtension(string $relativeRoot, string $extension): array
    {
        $root = $this->path($relativeRoot);

        if (!$this->files->isDirectory($root)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $entry) {
            if (!$entry instanceof SplFileInfo || !$entry->isFile()) {
                continue;
            }

            if (strtolower($entry->getExtension()) === strtolower($extension)) {
                $files[] = $entry->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    /**
     * @return list<string>
     */
    private function childDirectories(string $relativeRoot): array
    {
        $root = $this->path($relativeRoot);

        if (!$this->files->isDirectory($root)) {
            return [];
        }

        $directories = [];

        foreach (scandir($root) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if ($this->files->isDirectory($root . DIRECTORY_SEPARATOR . $entry)) {
                $directories[] = $entry;
            }
        }

        sort($directories);

        return $directories;
    }

    private function isClassBearingFile(string $contents): bool
    {
        return preg_match('/^namespace\s+[^;]+;/m', $contents) === 1
            && preg_match('/^\s*(?:abstract\s+|final\s+|readonly\s+)*(?:class|interface|trait|enum)\s+[A-Za-z_][A-Za-z0-9_]*/m', $contents) === 1;
    }

    private function hasStrictTypes(string $contents): bool
    {
        return preg_match('/declare\s*\(\s*strict_types\s*=\s*1\s*\)\s*;/', $contents) === 1;
    }

    private function isThinAlias(string $relative, string $target): bool
    {
        $contents = $this->read($relative);

        if ($contents === '') {
            return false;
        }

        return $this->contains($contents, 'extends \\' . $target);
    }

    private function pathExists(string $relative): bool
    {
        $path = $this->path($relative);

        return $this->files->fileExists($path) || $this->files->isDirectory($path);
    }

    private function read(string $relative): string
    {
        $contents = $this->files->readContents($this->path($relative));

        return is_string($contents) ? $contents : '';
    }

    private function relativePath(string $path): string
    {
        $normalizedBase = $this->normalizeSlashes($this->basePath);
        $normalizedPath = $this->normalizeSlashes($path);

        return ltrim(str_replace($normalizedBase, '', $normalizedPath), '/');
    }

    private function path(string $relative): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . ltrim($relative, '\\/');
    }

    private function normalizeSlashes(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
