<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Core\Config;
use App\Core\Database;
use App\Core\MigrationRunner;
use App\Core\Router;
use App\Core\SeedRunner;
use App\Core\Session;
use App\Exceptions\AuthException;
use App\Exceptions\Database\RepositoryException;
use App\Exceptions\Http\ServiceException;
use App\Contracts\Async\EventDispatcherInterface;
use App\Contracts\Support\HealthManagerInterface;
use App\Drivers\Session\FileSessionDriver;
use App\Modules\AdminModule\Services\AdminAccessService;
use App\Modules\CartModule\Migrations\CreateCartTables;
use App\Modules\CartModule\Repositories\CartItemRepository;
use App\Modules\CartModule\Repositories\PromotionRepository;
use App\Modules\ShopModule\Migrations\AddProductFulfillmentColumns;
use App\Modules\ShopModule\Migrations\CreateShopTables;
use App\Support\Commerce\CatalogLifecycleManager;
use App\Support\Commerce\CartPricingManager;
use App\Support\Commerce\CommerceTotalsCalculator;
use App\Support\Commerce\EntitlementManager;
use App\Support\Commerce\OrderLifecycleManager;
use App\Support\Commerce\PromotionManager;
use App\Support\Commerce\ShippingManager;
use App\Modules\UserModule\Migrations\CreateUserPlatformTables;
use App\Modules\UserModule\Repositories\PermissionRepository;
use App\Modules\UserModule\Repositories\RoleRepository;
use App\Modules\UserModule\Repositories\UserAuthTokenRepository;
use App\Modules\UserModule\Repositories\UserPasskeyRepository;
use App\Modules\UserModule\Repositories\UserRepository;
use App\Modules\UserModule\Seeds\UserPlatformSeed;
use App\Modules\UserModule\Services\UserAuthService;
use App\Modules\UserModule\Services\UserPasskeyService;
use App\Modules\UserModule\Services\UserProfileService;
use App\Modules\WebModule\Repositories\PageRepository;
use App\Providers\CoreProvider;
use App\Providers\ExceptionProvider;
use App\Utilities\Managers\CacheManager;
use App\Utilities\Managers\Async\QueueManager;
use App\Utilities\Managers\Data\CryptoManager;
use App\Utilities\Managers\Data\ModuleManager;
use App\Utilities\Managers\Data\SessionManager;
use App\Utilities\Managers\FileManager;
use App\Utilities\Managers\Security\AuthManager;
use App\Utilities\Managers\Security\DatabaseUserProvider;
use App\Utilities\Managers\Security\Gate;
use App\Utilities\Managers\Security\PasswordBroker;
use App\Utilities\Managers\Security\PermissionRegistry;
use App\Utilities\Managers\Security\PolicyResolver;
use App\Utilities\Managers\Security\SessionGuard;
use App\Utilities\Managers\Support\AuditLogger;
use App\Utilities\Managers\Support\MailManager;
use App\Utilities\Managers\Support\NotificationManager;
use App\Utilities\Managers\Support\PaymentManager;
use App\Utilities\Managers\Support\OtpManager;
use App\Utilities\Managers\Support\PasskeyManager;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Managers\SettingsManager;
use OTPHP\TOTP;
use PDO;
use PHPUnit\Framework\TestCase;

class AuthPlatformTest extends TestCase
{
    private array $sessionBackup = [];
    private array $cookieBackup = [];
    private array $serverBackup = [];
    private array $postBackup = [];
    private array $getBackup = [];

    protected function setUp(): void
    {
        $this->sessionBackup = $_SESSION ?? [];
        $this->cookieBackup = $_COOKIE ?? [];
        $this->serverBackup = $_SERVER ?? [];
        $this->postBackup = $_POST ?? [];
        $this->getBackup = $_GET ?? [];
        $_SESSION = [];
        $_COOKIE = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = $this->sessionBackup;
        $_COOKIE = $this->cookieBackup;
        $_SERVER = $this->serverBackup;
        $_POST = $this->postBackup;
        $_GET = $this->getBackup;
    }

    public function testUserPlatformMigrationSeedAndAuthManagerSupportRbac(): void
    {
        $database = $this->makeSqliteDatabase();
        $modules = $this->makeModuleManagerStub([
            CreateUserPlatformTables::class,
            UserPlatformSeed::class,
        ]);
        $errors = new ErrorManager(new ExceptionProvider());

        (new MigrationRunner($database, $modules, $errors))->migrate('UserModule');
        (new SeedRunner($database, $modules, $errors))->run('UserModule');

        $stack = $this->makeAuthStack($database);

        self::assertTrue($stack['auth']->attempt([
            'email' => 'admin@langelermvc.test',
            'password' => 'admin12345',
        ]));
        self::assertTrue($stack['auth']->check());
        self::assertTrue($stack['auth']->hasRole('administrator'));
        self::assertTrue($stack['auth']->hasPermission('admin.access'));
        self::assertContains('admin.users.manage', $stack['auth']->currentPermissions());

        self::assertTrue($stack['passwords']->sendResetLink('customer@langelermvc.test'));
        self::assertCount(1, $stack['mail']->outbox());
        self::assertSame(1, count($stack['tokens']->activeTokens(2, 'password_reset')));

        $stack['auth']->logout();
        self::assertTrue($stack['auth']->guest());
    }

    public function testUserAuthServiceRegistersUsersAndSupportsOtpProvisioning(): void
    {
        $database = $this->makeSqliteDatabase();
        $modules = $this->makeModuleManagerStub([
            CreateUserPlatformTables::class,
            UserPlatformSeed::class,
        ]);
        $errors = new ErrorManager(new ExceptionProvider());

        (new MigrationRunner($database, $modules, $errors))->migrate('UserModule');
        (new SeedRunner($database, $modules, $errors))->run('UserModule');

        $stack = $this->makeAuthStack($database);

        $service = new UserAuthService(
            $stack['users'],
            $stack['roles'],
            $stack['tokens'],
            $stack['provider'],
            $stack['auth'],
            $stack['passwords'],
            $stack['mail'],
            $stack['otp'],
            $stack['crypto'],
            $stack['config'],
            $stack['errors'],
            $stack['audit']
        );

        $registered = $service->forAction('register', [
            'name' => 'Jane Example',
            'email' => 'jane@example.com',
            'password' => 'supersecret123',
            'password_confirmation' => 'supersecret123',
            'remember' => true,
        ])->execute();

        self::assertSame(201, $registered['status']);
        self::assertSame(3, $stack['users']->count([]));
        self::assertTrue($stack['auth']->check());
        self::assertSame(['customer'], $stack['users']->rolesForUser((int) $stack['auth']->id()));
        self::assertCount(1, $stack['mail']->outbox());

        $otpProvision = $service->forAction('enableOtp')->execute();
        self::assertSame(200, $otpProvision['status']);
        self::assertNotEmpty($otpProvision['otp']['uri']);
        self::assertCount(8, $otpProvision['recoveryCodes']);

        $code = TOTP::create(
            $otpProvision['otp']['secret'],
            30,
            'sha1',
            6
        )->now();

        $verified = $service->forAction('verifyOtp', ['otp_code' => $code])->execute();
        self::assertSame(200, $verified['status']);
        self::assertTrue((bool) ($verified['user']['otpEnabled'] ?? false));
    }

    public function testOtpRecoveryCodesCanBeUsedRegeneratedAndDisabled(): void
    {
        $database = $this->makeSqliteDatabase();
        $modules = $this->makeModuleManagerStub([
            CreateUserPlatformTables::class,
            UserPlatformSeed::class,
        ]);
        $errors = new ErrorManager(new ExceptionProvider());

        (new MigrationRunner($database, $modules, $errors))->migrate('UserModule');
        (new SeedRunner($database, $modules, $errors))->run('UserModule');

        $stack = $this->makeAuthStack($database);
        self::assertTrue($stack['auth']->attempt([
            'email' => 'customer@langelermvc.test',
            'password' => 'customer12345',
        ]));

        $service = new UserAuthService(
            $stack['users'],
            $stack['roles'],
            $stack['tokens'],
            $stack['provider'],
            $stack['auth'],
            $stack['passwords'],
            $stack['mail'],
            $stack['otp'],
            $stack['crypto'],
            $stack['config'],
            $stack['errors'],
            $stack['audit']
        );

        $provisioned = $service->forAction('enableOtp')->execute();
        $code = TOTP::create(
            $provisioned['otp']['secret'],
            30,
            'sha1',
            6
        )->now();

        $verified = $service->forAction('verifyOtp', ['otp_code' => $code])->execute();
        $recoveryCode = (string) ($verified['recoveryCodes'][0] ?? '');

        $stack['auth']->logout();
        $requiresOtp = $service->forAction('login', [
            'email' => 'customer@langelermvc.test',
            'password' => 'customer12345',
        ])->execute();

        self::assertSame(202, $requiresOtp['status']);
        self::assertTrue((bool) ($requiresOtp['requiresOtp'] ?? false));

        $recovered = $service->forAction('login', [
            'email' => 'customer@langelermvc.test',
            'password' => 'customer12345',
            'recovery_code' => $recoveryCode,
        ])->execute();

        self::assertSame(200, $recovered['status']);
        self::assertTrue($stack['auth']->check());

        $regenerated = $service->forAction('regenerateOtpRecoveryCodes')->execute();
        self::assertSame(200, $regenerated['status']);
        self::assertCount(8, $regenerated['recoveryCodes']);

        $disabled = $service->forAction('disableOtp')->execute();
        self::assertSame(200, $disabled['status']);
        self::assertFalse((bool) ($disabled['user']['otpEnabled'] ?? true));
    }

    public function testPasskeyServiceRegistersAuthenticatesAndDeletesPasskeys(): void
    {
        $database = $this->makeSqliteDatabase();
        $modules = $this->makeModuleManagerStub([
            CreateUserPlatformTables::class,
            UserPlatformSeed::class,
        ]);
        $errors = new ErrorManager(new ExceptionProvider());

        (new MigrationRunner($database, $modules, $errors))->migrate('UserModule');
        (new SeedRunner($database, $modules, $errors))->run('UserModule');

        $stack = $this->makeAuthStack($database);
        self::assertTrue($stack['auth']->attempt([
            'email' => 'customer@langelermvc.test',
            'password' => 'customer12345',
        ]));

        $service = new UserPasskeyService(
            $stack['users'],
            $stack['passkeys'],
            $stack['auth'],
            $stack['passkeyManager'],
            $stack['errors'],
            $stack['audit']
        );

        $options = $service->forAction('beginRegistration', [
            'passkey_name' => 'Laptop Passkey',
        ])->execute();

        self::assertSame(200, $options['status']);
        self::assertSame('registration', $options['passkey']['flow']);

        $registered = $service->forAction('finishRegistration', [
            'credential' => [
                'id' => 'passkey-customer-1',
                'rawId' => 'passkey-customer-1',
                'type' => 'public-key',
                'transports' => ['internal'],
            ],
        ])->execute();

        self::assertSame(201, $registered['status']);
        self::assertCount(1, $stack['passkeys']->allForUserData(2));

        $stack['auth']->logout();

        $authOptions = $service->forAction('beginAuthentication', [
            'email' => 'customer@langelermvc.test',
            'remember' => true,
        ])->execute();

        self::assertSame(200, $authOptions['status']);
        self::assertSame('authentication', $authOptions['passkey']['flow']);

        $authenticated = $service->forAction('finishAuthentication', [
            'credential' => [
                'id' => 'passkey-customer-1',
                'rawId' => 'passkey-customer-1',
                'type' => 'public-key',
            ],
        ])->execute();

        self::assertSame(200, $authenticated['status']);
        self::assertTrue($stack['auth']->check());
        self::assertSame('customer@langelermvc.test', $authenticated['user']['email']);

        $passkeyId = (int) ($stack['passkeys']->allForUserData(2)[0]['id'] ?? 0);
        $deleted = $service->forAction('deletePasskey', [], ['passkey' => $passkeyId])->execute();

        self::assertSame(200, $deleted['status']);
        self::assertCount(0, $stack['passkeys']->allForUserData(2));
    }

    public function testUserProfileServiceUsesFrameworkAuthContextWhenNoUserIsAuthenticated(): void
    {
        $database = $this->makeSqliteDatabase();
        $modules = $this->makeModuleManagerStub([
            CreateUserPlatformTables::class,
            UserPlatformSeed::class,
        ]);
        $errors = new ErrorManager(new ExceptionProvider());

        (new MigrationRunner($database, $modules, $errors))->migrate('UserModule');
        (new SeedRunner($database, $modules, $errors))->run('UserModule');

        $stack = $this->makeAuthStack($database);
        $service = new UserProfileService(
            $stack['users'],
            $stack['passkeys'],
            $stack['tokens'],
            $stack['provider'],
            $stack['auth'],
            $stack['config'],
            $stack['passkeyManager'],
            $stack['audit']
        );

        try {
            $service->execute();
            self::fail('Expected the profile service to reject unauthenticated access.');
        } catch (ServiceException $exception) {
            self::assertInstanceOf(AuthException::class, $exception->getPrevious());
            self::assertSame('Authenticated user is required.', $exception->getPrevious()?->getMessage());
        }
    }

    public function testCartItemRepositoryUsesRepositoryExceptionForMissingQuantityUpdates(): void
    {
        $database = $this->makeSqliteDatabase();
        $modules = $this->makeModuleManagerStub([
            CreateUserPlatformTables::class,
            CreateShopTables::class,
            AddProductFulfillmentColumns::class,
            CreateCartTables::class,
        ]);
        $errors = new ErrorManager(new ExceptionProvider());

        (new MigrationRunner($database, $modules, $errors))->migrate();

        $repository = new CartItemRepository($database);

        $this->expectException(RepositoryException::class);
        $this->expectExceptionMessage('Cart item [999] could not be found.');
        $repository->updateQuantity(999, 2);
    }

    public function testAdminServiceExposesDashboardAndRoleManagement(): void
    {
        $database = $this->makeSqliteDatabase();
        $modules = $this->makeModuleManagerStub([
            CreateUserPlatformTables::class,
            UserPlatformSeed::class,
        ]);
        $errors = new ErrorManager(new ExceptionProvider());

        (new MigrationRunner($database, $modules, $errors))->migrate('UserModule');
        (new SeedRunner($database, $modules, $errors))->run('UserModule');

        $stack = $this->makeAuthStack($database);
        $stack['auth']->attempt([
            'email' => 'admin@langelermvc.test',
            'password' => 'admin12345',
        ]);

        $moduleManager = $this->createStub(ModuleManager::class);
        $moduleManager->method('getModules')->willReturn([
            'WebModule' => '/tmp/WebModule',
            'UserModule' => '/tmp/UserModule',
            'AdminModule' => '/tmp/AdminModule',
        ]);

        $cache = $this->createStub(CacheManager::class);
        $cache->method('capabilities')->willReturn(['driver' => ['array' => true]]);

        $router = $this->createStub(Router::class);
        $router->method('listRoutes')->willReturn([
            ['method' => 'GET', 'path' => '/admin', 'action' => 'AdminController@dashboard', 'name' => 'admin.dashboard', 'middleware' => []],
        ]);

        $service = new AdminAccessService(
            $stack['auth'],
            $stack['users'],
            $stack['roles'],
            $stack['permissions'],
            $this->createStub(PageRepository::class),
            $this->createStub(\App\Modules\ShopModule\Repositories\ProductRepository::class),
            $this->createStub(\App\Modules\ShopModule\Repositories\CategoryRepository::class),
            $this->createStub(\App\Modules\CartModule\Repositories\CartRepository::class),
            $this->createStub(\App\Modules\CartModule\Repositories\CartItemRepository::class),
            $this->createStub(PromotionRepository::class),
            $this->createStub(\App\Modules\OrderModule\Repositories\OrderRepository::class),
            $this->createStub(\App\Modules\OrderModule\Repositories\OrderItemRepository::class),
            $this->createStub(\App\Modules\OrderModule\Repositories\OrderAddressRepository::class),
            $this->createStub(CatalogLifecycleManager::class),
            new CartPricingManager(
                $this->createStub(ShippingManager::class),
                new PromotionManager($stack['config']),
                new CommerceTotalsCalculator($stack['config'])
            ),
            new CommerceTotalsCalculator($stack['config']),
            $this->createStub(OrderLifecycleManager::class),
            $this->createStub(ShippingManager::class),
            $this->createStub(EntitlementManager::class),
            $moduleManager,
            $cache,
            $stack['sessionManager'],
            $this->createStub(QueueManager::class),
            $this->createStub(NotificationManager::class),
            $this->createStub(PaymentManager::class),
            $this->createStub(EventDispatcherInterface::class),
            $this->createStub(HealthManagerInterface::class),
            $stack['audit'],
            $router,
            $stack['config']
        );

        $dashboard = $service->forAction('dashboard')->execute();
        self::assertSame(2, $dashboard['metrics']['users']);
        self::assertSame(2, $dashboard['metrics']['roles']);
        self::assertContains('UserModule', $dashboard['modules']);

        $users = $service->forAction('users')->execute();
        self::assertSame(200, $users['status']);
        self::assertCount(2, $users['users']);

        $assigned = $service->forAction('assignRoles', ['roles' => ['administrator', 'customer']], ['user' => 2])->execute();
        self::assertSame(200, $assigned['status']);
        self::assertContains('administrator', $stack['users']->rolesForUser(2));

        $synced = $service->forAction('syncPermissions', ['permissions' => ['cart.manage']], ['role' => 2])->execute();
        self::assertSame(200, $synced['status']);
        self::assertContains('cart.manage', $stack['roles']->permissionsForRole(2));
    }

    public function testOtpTrustedDevicesSupportPasswordlessChallengeBypassUntilRevoked(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Trusted Device Browser';

        $database = $this->makeSqliteDatabase();
        $modules = $this->makeModuleManagerStub([
            CreateUserPlatformTables::class,
            UserPlatformSeed::class,
        ]);
        $errors = new ErrorManager(new ExceptionProvider());

        (new MigrationRunner($database, $modules, $errors))->migrate('UserModule');
        (new SeedRunner($database, $modules, $errors))->run('UserModule');

        $stack = $this->makeAuthStack($database);
        self::assertTrue($stack['auth']->attempt([
            'email' => 'customer@langelermvc.test',
            'password' => 'customer12345',
        ]));

        $service = new UserAuthService(
            $stack['users'],
            $stack['roles'],
            $stack['tokens'],
            $stack['provider'],
            $stack['auth'],
            $stack['passwords'],
            $stack['mail'],
            $stack['otp'],
            $stack['crypto'],
            $stack['config'],
            $stack['errors'],
            $stack['audit']
        );

        $provisioned = $service->forAction('enableOtp')->execute();
        $code = TOTP::create(
            $provisioned['otp']['secret'],
            30,
            'sha1',
            6
        )->now();

        $verified = $service->forAction('verifyOtp', [
            'otp_code' => $code,
            'trust_device' => true,
        ])->execute();

        self::assertSame(200, $verified['status']);
        self::assertCount(1, $stack['tokens']->activeTokenPayloads(2, 'otp_trusted_device'));
        self::assertArrayHasKey('langelermvc_otp_trusted', $_COOKIE);

        $stack['auth']->logout();

        $trustedLogin = $service->forAction('login', [
            'email' => 'customer@langelermvc.test',
            'password' => 'customer12345',
        ])->execute();

        self::assertSame(200, $trustedLogin['status']);
        self::assertTrue($stack['auth']->check());

        $profile = new UserProfileService(
            $stack['users'],
            $stack['passkeys'],
            $stack['tokens'],
            $stack['provider'],
            $stack['auth'],
            $stack['config'],
            $stack['passkeyManager'],
            $stack['audit']
        );
        $profilePage = $profile->execute();

        self::assertCount(1, $profilePage['trustedDevices']);

        $revoked = $service->forAction('revokeTrustedDevices')->execute();
        self::assertSame(200, $revoked['status']);
        self::assertCount(0, $stack['tokens']->activeTokenPayloads(2, 'otp_trusted_device'));
        self::assertArrayNotHasKey('langelermvc_otp_trusted', $_COOKIE);
    }

    public function testRouterListsNewPlatformRoutesAndShortCircuitsProtectedAdminMiddleware(): void
    {
        $provider = new CoreProvider();
        $provider->registerServices();
        $router = $provider->getCoreService('router');

        $listed = $router->listRoutes();
        $names = array_values(array_filter(array_map(
            static fn(array $route): ?string => $route['name'],
            $listed
        )));

        self::assertContains('user.login', $names);
        self::assertContains('admin.dashboard', $names);
        self::assertContains('admin.pages', $names);
        self::assertContains('admin.promotions', $names);
        self::assertContains('api.user.passkeys.login.options', $names);
        self::assertContains('user.passkeys.register.options', $names);

        $_SERVER['REQUEST_URI'] = '/admin';
        $htmlResponse = $router->dispatch('/admin', 'GET');
        self::assertSame(302, $htmlResponse->getStatus());
        self::assertSame('/users/login', $htmlResponse->getHeaders()['location']);

        $_SERVER['REQUEST_URI'] = '/api/admin';
        $_SERVER['HTTP_ACCEPT'] = 'application/json';
        $jsonResponse = $router->dispatch('/api/admin', 'GET');
        self::assertSame(401, $jsonResponse->getStatus());
        self::assertStringContainsString('Authentication required', (string) $jsonResponse->toArray()['content']);
    }

    private function makeSqliteDatabase(): Database
    {
        $settings = new class extends SettingsManager {
            public function __construct() {}

            public function getAllSettings(string $fileName): array
            {
                return [
                    'DRIVER' => 'sqlite',
                    'CONNECTION' => 'sqlite',
                    'DATABASE' => ':memory:',
                ];
            }
        };

        return new Database(
            $settings,
            new ErrorManager(new ExceptionProvider()),
            new PDO('sqlite::memory:')
        );
    }

    /**
     * @param array<int, class-string> $classes
     */
    private function makeModuleManagerStub(array $classes): ModuleManager
    {
        return new class($classes) extends ModuleManager {
            public function __construct(private array $classes)
            {
            }

            public function getClasses(string $module, string $subDir, array $filter = ['extension' => 'php'], array $sort = []): array
            {
                return $this->filterClasses($module, $subDir);
            }

            public function collectClasses(string $subDir, array $filter = ['extension' => 'php'], array $sort = []): array
            {
                return $this->filterClasses(null, $subDir);
            }

            private function filterClasses(?string $module, string $subDir): array
            {
                $results = [];

                foreach ($this->classes as $class) {
                    if ($subDir !== '' && !str_contains($class, '\\' . $subDir . '\\')) {
                        continue;
                    }

                    if ($module !== null && !str_contains($class, '\\' . $module . '\\')) {
                        continue;
                    }

                    $segments = explode('\\', $class);
                    $shortName = (string) end($segments);

                    $results[] = [
                        'class' => $class,
                        'shortName' => $shortName,
                        'file' => str_replace('\\', '/', $class) . '.php',
                    ];
                }

                return $results;
            }
        };
    }

    /**
     * @return array{
     *   auth: AuthManager,
     *   audit: AuditLogger,
     *   cache: CacheManager,
     *   config: Config,
     *   crypto: CryptoManager,
     *   errors: ErrorManager,
     *   mail: MailManager,
     *   otp: OtpManager,
     *   passkeyManager: PasskeyManager,
     *   passkeys: UserPasskeyRepository,
     *   passwords: PasswordBroker,
     *   permissions: PermissionRepository,
     *   provider: DatabaseUserProvider,
     *   roles: RoleRepository,
     *   sessionManager: SessionManager,
     *   tokens: UserAuthTokenRepository,
     *   users: UserRepository
     * }
     */
    private function makeAuthStack(Database $database): array
    {
        $errors = new ErrorManager(new ExceptionProvider());
        $config = new class extends Config {
            public function __construct() {}

            public function get(string $file, ?string $key = null, mixed $default = null): mixed
            {
                $settings = [
                    'app' => [
                        'NAME' => 'LangelerMVC',
                        'VERSION' => '1.0.0',
                        'URL' => 'https://langelermvc.test',
                    ],
                    'auth' => [
                        'GUARD' => 'session',
                        'USER_REPOSITORY' => \App\Modules\UserModule\Repositories\UserRepository::class,
                        'PASSWORD_HASHER' => 'default',
                        'SESSION_KEY' => 'auth.user_id',
                        'VIA_REMEMBER_KEY' => 'auth.via_remember',
                        'REMEMBER_COOKIE' => 'langelermvc_remember',
                        'VERIFY_EMAIL' => true,
                        'EMAIL_VERIFY_EXPIRES' => 1440,
                        'PASSWORD_RESET_EXPIRES' => 60,
                        'REMEMBER_ME_DAYS' => 30,
                        'DEFAULT_ROLE' => 'customer',
                        'ADMIN_ROLE' => 'administrator',
                        'PERMISSIONS' => [
                            'admin.access',
                            'admin.system.view',
                            'admin.users.manage',
                            'admin.roles.manage',
                            'content.manage',
                            'user.profile.view',
                            'user.profile.update',
                            'shop.catalog.manage',
                            'promotion.manage',
                            'cart.manage',
                            'order.manage',
                        ],
                        'OTP' => [
                            'DIGITS' => 6,
                            'PERIOD' => 30,
                            'ALGORITHM' => 'sha1',
                            'RECOVERY_CODES' => 8,
                            'TRUSTED_DEVICE_DAYS' => 30,
                            'TRUSTED_DEVICE_COOKIE' => 'langelermvc_otp_trusted',
                        ],
                        'PASSKEY' => [
                            'DRIVER' => 'testing',
                            'RP_ID' => 'langelermvc.test',
                            'RP_NAME' => 'LangelerMVC',
                            'ORIGINS' => ['https://langelermvc.test'],
                            'ALLOW_SUBDOMAINS' => false,
                            'TIMEOUT' => 60000,
                            'CHALLENGE_TTL' => 300,
                            'CHALLENGE_BYTES' => 32,
                            'ATTACHMENT' => null,
                            'RESIDENT_KEY' => 'preferred',
                            'ATTESTATION' => 'none',
                            'REGISTRATION' => [
                                'USER_VERIFICATION' => 'preferred',
                            ],
                            'AUTHENTICATION' => [
                                'USER_VERIFICATION' => 'preferred',
                            ],
                        ],
                    ],
                    'mail' => [
                        'MAILER' => 'array',
                        'FROM' => 'LangelerMVC <no-reply@langelermvc.test>',
                    ],
                    'session' => [
                        'DRIVER' => 'file',
                        'NAME' => 'langelermvc_session',
                        'LIFETIME' => 120,
                        'COOKIE' => [
                            'PATH' => '/',
                            'DOMAIN' => '',
                            'SECURE' => false,
                            'HTTPONLY' => true,
                            'SAME_SITE' => 'Lax',
                        ],
                        'SAVE' => [
                            'PATH' => sys_get_temp_dir() . '/langelermvc-sessions',
                        ],
                        'GC' => [
                            'PROBABILITY' => 1,
                            'DIVISOR' => 100,
                            'MAX_LIFETIME' => 1440,
                        ],
                        'NATIVE' => [
                            'HANDLER' => 'files',
                            'STRICT_MODE' => true,
                            'USE_COOKIES' => true,
                            'USE_ONLY_COOKIES' => true,
                            'SID_LENGTH' => 48,
                        ],
                        'DATABASE' => [
                            'TABLE' => 'framework_sessions',
                        ],
                    ],
                    'operations' => [
                        'AUDIT' => [
                            'ENABLED' => true,
                            'SUMMARY_LIMIT' => 250,
                        ],
                    ],
                ];

                $bucket = $settings[strtolower($file)] ?? null;

                if ($key === null) {
                    return $bucket ?? $default;
                }

                if (!is_array($bucket)) {
                    return $default;
                }

                $current = $bucket;

                foreach (explode('.', $key) as $segment) {
                    if (!is_array($current) || !array_key_exists($segment, $current)) {
                        return $default;
                    }

                    $current = $current[$segment];
                }

                return $current;
            }
        };

        $crypto = $this->createStub(CryptoManager::class);
        $crypto->method('generateRandom')->willReturnCallback(function (string $type, mixed ...$arguments): string {
            if ($type === 'generateRandomIv') {
                return random_bytes(16);
            }

            $length = isset($arguments[0]) && is_int($arguments[0]) ? $arguments[0] : 32;

            return random_bytes($length);
        });
        $crypto->method('getDriverName')->willReturn('openssl');
        $crypto->method('resolveConfiguredKey')->willReturn(str_repeat('k', 32));
        $crypto->method('resolveConfiguredCipher')->willReturn('AES-256-CBC');
        $crypto->method('ivLength')->willReturn(16);
        $crypto->method('encrypt')->willReturnCallback(
            static fn(string $type, string $value, mixed ...$arguments): string => base64_encode($value)
        );
        $crypto->method('decrypt')->willReturnCallback(
            static fn(string $type, string $value, mixed ...$arguments): string => (string) (base64_decode($value, true) ?: '')
        );
        $crypto->method('passwordHash')->willReturnCallback(
            static fn(string $algorithm, string $value): string => (string) password_hash($value, PASSWORD_DEFAULT)
        );
        $crypto->method('passwordVerify')->willReturnCallback(
            static fn(string $hash, string $password, string $action = 'verify'): bool => password_verify($password, $hash)
        );
        $crypto->method('passwordNeedsRehash')->willReturn(false);

        $fileManager = new FileManager();
        $sessionManager = new SessionManager($fileManager, $errors, $database);
        $session = new Session($config, $sessionManager, $crypto, $errors);
        $mail = new MailManager($config, $fileManager, $errors);
        $otp = new OtpManager($config);
        $passkeyManager = new PasskeyManager($config, $session, $errors);

        $users = new UserRepository($database);
        $passkeys = new UserPasskeyRepository($database);
        $roles = new RoleRepository($database);
        $permissions = new PermissionRepository($database);
        $tokens = new UserAuthTokenRepository($database);
        $provider = new DatabaseUserProvider($config, $database, $crypto, $errors);
        $guard = new SessionGuard($session, $config, $provider, $crypto, $errors);
        $registry = new PermissionRegistry($config);
        $gate = new Gate($guard, $provider, $registry, new PolicyResolver());
        $passwords = new PasswordBroker($config, $provider, $users, $tokens, $crypto, $mail, $errors);
        $audit = new AuditLogger($database, $config, $errors);
        $events = new class implements EventDispatcherInterface {
            public function listen(string $event, callable|string|array $listener, bool $queued = false, string $queue = 'default'): void {}
            public function subscribe(array $listeners): void {}
            public function dispatch(string|object $event, array $payload = []): array { return []; }
            public function listeners(?string $event = null): array { return []; }
        };
        $auth = new AuthManager($guard, $gate, $passwords, $provider, $registry, $events, $audit);

        return [
            'auth' => $auth,
            'audit' => $audit,
            'cache' => $this->createStub(CacheManager::class),
            'config' => $config,
            'crypto' => $crypto,
            'errors' => $errors,
            'mail' => $mail,
            'otp' => $otp,
            'passkeyManager' => $passkeyManager,
            'passkeys' => $passkeys,
            'passwords' => $passwords,
            'permissions' => $permissions,
            'provider' => $provider,
            'roles' => $roles,
            'sessionManager' => $sessionManager,
            'tokens' => $tokens,
            'users' => $users,
        ];
    }
}
