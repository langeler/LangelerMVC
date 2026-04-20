<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Core\Config;
use App\Core\Router;
use App\Utilities\Managers\Data\ModuleManager;
use App\Utilities\Managers\Support\FrameworkDoctor;
use App\Utilities\Managers\System\FileManager;
use App\Utilities\Managers\System\SettingsManager;
use PHPUnit\Framework\TestCase;

class FrameworkDoctorTest extends TestCase
{
    private string $moduleRoot;

    protected function setUp(): void
    {
        $this->moduleRoot = sys_get_temp_dir() . '/langelermvc-doctor-module-' . bin2hex(random_bytes(4));
        $this->createModuleSurfaces($this->moduleRoot);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->moduleRoot);
    }

    public function testFrameworkDoctorReportsHealthyWhenSurfaceIsComplete(): void
    {
        $doctor = $this->makeDoctor($this->baseConfig([
            'app' => [
                'ENV' => 'production',
                'DEBUG' => 'false',
                'INSTALLED' => 'true',
                'URL' => 'https://example.test',
            ],
            'session' => [
                'COOKIE' => ['SECURE' => 'true'],
            ],
            'http' => [
                'SIGNED_URL' => ['KEY' => 'doctor-signed-url-key-123456'],
            ],
            'encryption' => [
                'ENABLED' => 'true',
                'DRIVER' => 'openssl',
                'KEY' => 'base64:Zm9vYmFyYmF6cXV4cXV1eA==',
                'OPENSSL_KEY' => 'base64:b3BlbnNzbC1zYW1wbGUta2V5',
                'SODIUM_KEY' => 'base64:c29kaXVtLXNhbXBsZS1rZXk=',
                'PBKDF2_ITERATIONS' => '120000',
            ],
        ]));

        $report = $doctor->inspect();

        self::assertTrue((bool) $report['healthy']);
        self::assertSame(200, $report['status']);
        self::assertSame([], $report['errors']);
    }

    public function testFrameworkDoctorStrictModeFailsWhenWarningsExist(): void
    {
        $doctor = $this->makeDoctor($this->baseConfig([
            'app' => [
                'ENV' => 'production',
                'DEBUG' => 'false',
                'INSTALLED' => 'false',
                'URL' => 'https://example.test',
            ],
            'session' => [
                'COOKIE' => ['SECURE' => 'true'],
            ],
            'http' => [
                'SIGNED_URL' => ['KEY' => 'doctor-signed-url-key-123456'],
            ],
            'encryption' => [
                'ENABLED' => 'true',
                'DRIVER' => 'openssl',
                'KEY' => 'base64:Zm9vYmFyYmF6cXV4cXV1eA==',
                'OPENSSL_KEY' => 'base64:b3BlbnNzbC1zYW1wbGUta2V5',
                'SODIUM_KEY' => 'base64:c29kaXVtLXNhbXBsZS1rZXk=',
                'PBKDF2_ITERATIONS' => '120000',
            ],
        ]));

        $nonStrict = $doctor->inspect(false);
        $strict = $doctor->inspect(true);

        self::assertTrue((bool) $nonStrict['healthy']);
        self::assertFalse((bool) $strict['healthy']);
        self::assertSame(503, $strict['status']);
        self::assertNotSame([], $strict['warnings']);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function makeDoctor(array $config): FrameworkDoctor
    {
        $runtimeConfig = new class($config) extends Config {
            /**
             * @param array<string, mixed> $config
             */
            public function __construct(private array $config)
            {
            }

            public function all(): array
            {
                return $this->config;
            }

            public function get(string $file, ?string $key = null, mixed $default = null): mixed
            {
                $bucket = $this->config[strtolower($file)] ?? null;

                if ($key === null || $key === '') {
                    return $bucket ?? $default;
                }

                if (!is_array($bucket)) {
                    return $default;
                }

                $current = $bucket;

                foreach (explode('.', $key) as $segment) {
                    if (!is_array($current)) {
                        return $default;
                    }

                    if (array_key_exists($segment, $current)) {
                        $current = $current[$segment];
                        continue;
                    }

                    $matched = null;

                    foreach ($current as $candidate => $value) {
                        if (strcasecmp((string) $candidate, $segment) === 0) {
                            $matched = $value;
                            break;
                        }
                    }

                    if ($matched === null) {
                        return $default;
                    }

                    $current = $matched;
                }

                return $current;
            }
        };

        $settings = new class extends SettingsManager {
            public function __construct()
            {
            }

            public function getInvalidFiles(): array
            {
                return [];
            }
        };

        $modules = new class($this->moduleRoot) extends ModuleManager {
            public function __construct(private string $moduleRoot)
            {
            }

            public function getModules(): array
            {
                return [
                    'DoctorSpecModule' => $this->moduleRoot,
                ];
            }
        };

        $router = new class extends Router {
            public function __construct()
            {
            }

            public function listRoutes(): array
            {
                return [[
                    'method' => 'GET',
                    'path' => '/doctor-spec',
                    'action' => 'DoctorSpecController@index',
                    'name' => 'doctor.spec',
                    'middleware' => [],
                ]];
            }
        };

        return new FrameworkDoctor(
            $runtimeConfig,
            $settings,
            $modules,
            $router,
            new FileManager()
        );
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function baseConfig(array $overrides = []): array
    {
        $base = [
            'app' => [],
            'auth' => [],
            'cache' => [],
            'cookie' => [],
            'db' => [],
            'encryption' => [],
            'http' => [],
            'mail' => [],
            'notifications' => [],
            'operations' => [],
            'payment' => [],
            'queue' => [],
            'session' => [],
            'webmodule' => [],
        ];

        return array_replace_recursive($base, $overrides);
    }

    private function createModuleSurfaces(string $root): void
    {
        $surfaces = [
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

        @mkdir($root, 0777, true);

        foreach ($surfaces as $surface) {
            @mkdir($root . DIRECTORY_SEPARATOR . $surface, 0777, true);
        }
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $entries = scandir($path);

        if (!is_array($entries)) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $target = $path . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($target)) {
                $this->removeDirectory($target);
                continue;
            }

            @unlink($target);
        }

        @rmdir($path);
    }
}
