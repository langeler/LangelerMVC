<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Core\Config;
use App\Core\Session;
use App\Providers\CoreProvider;
use App\Providers\CryptoProvider;
use App\Providers\ExceptionProvider;
use App\Utilities\Managers\Data\CryptoManager;
use App\Utilities\Managers\FileManager;
use App\Utilities\Managers\SessionManager;
use App\Utilities\Managers\SettingsManager;
use App\Utilities\Managers\System\ErrorManager;
use PHPUnit\Framework\TestCase;

class SessionSubsystemTest extends TestCase
{
    protected function tearDown(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }

        $_SESSION = [];
    }

    public function testSessionManagerNormalizesLegacyAndNestedConfiguration(): void
    {
        $manager = new SessionManager(
            new FileManager(),
            new ErrorManager(new ExceptionProvider())
        );

        $normalized = $manager->normalizeConfiguration([
            'DRIVER' => ' native ',
            'NAME' => ' langelermvc_test ',
            'LIFETIME' => '90',
            'SECURE' => 'false',
            'HTTPONLY' => 'true',
            'SAME' => 'strict',
            'SAVE' => 'Storage/Sessions',
            'GC' => '1800',
            'NATIVE' => 'files',
        ]);

        self::assertSame('native', $normalized['DRIVER']);
        self::assertSame('langelermvc_test', $normalized['NAME']);
        self::assertSame(90, $normalized['LIFETIME']);
        self::assertFalse($normalized['COOKIE']['SECURE']);
        self::assertTrue($normalized['COOKIE']['HTTPONLY']);
        self::assertSame('Strict', $normalized['COOKIE']['SAME_SITE']);
        self::assertSame('Storage/Sessions', $normalized['SAVE']['PATH']);
        self::assertSame(1800, $normalized['GC']['MAX_LIFETIME']);
        self::assertSame('files', $normalized['NATIVE']['HANDLER']);
        self::assertTrue($manager->supports('handlers.files'));
        self::assertTrue($manager->supports('flash_data'));
    }

    public function testSessionFacadeSupportsFlashTokensAndMutationHelpers(): void
    {
        $provider = new CoreProvider();
        $provider->registerServices();

        $session = $provider->getCoreService('session');

        self::assertInstanceOf(Session::class, $session);
        self::assertTrue($session->start());
        self::assertTrue($session->isEphemeral());

        $session->putMany([
            'framework' => 'alive',
            'counter' => 1,
        ]);
        $session->push('messages', 'first');
        $session->push('messages', 'second');
        $session->flash('notice', 'saved');
        $session->now('instant', 'now');

        $firstToken = $session->token();
        $secondToken = $session->regenerateToken();

        self::assertNotSame($firstToken, $secondToken);
        self::assertSame('alive', $session->get('framework'));
        self::assertSame(['framework' => 'alive', 'counter' => 1], $session->only(['framework', 'counter']));
        self::assertArrayNotHasKey('counter', $session->except(['counter']));
        self::assertSame(['first', 'second'], $session->get('messages'));
        self::assertSame(2, $session->increment('counter'));
        self::assertSame(1, $session->decrement('counter'));
        self::assertSame('alive', $session->pull('framework'));
        self::assertTrue($session->missing('framework'));
        self::assertSame('saved', $session->get('notice'));
        self::assertSame('now', $session->get('instant'));

        self::assertTrue($session->close());
        self::assertTrue($session->start());
        self::assertSame('saved', $session->get('notice'));
        self::assertNull($session->get('instant'));

        self::assertTrue($session->close());
        self::assertTrue($session->start());
        self::assertNull($session->get('notice'));
        self::assertTrue($session->invalidate());
    }

    public function testSessionRejectsUnsupportedFrameworkLevelConfiguration(): void
    {
        $session = new Session(
            new class extends Config {
                public function __construct()
                {
                }

                public function get(string $file, ?string $key = null, mixed $default = null): mixed
                {
                    if (strtolower($file) !== 'session') {
                        return $default;
                    }

                    return [
                        'DRIVER' => 'cookie',
                    ];
                }
            },
            new SessionManager(
                new FileManager(),
                new ErrorManager(new ExceptionProvider())
            ),
            new CryptoManager(new CryptoProvider(), $this->makeSettingsManager()),
            new ErrorManager(new ExceptionProvider())
        );

        $this->expectException(\App\Exceptions\SessionException::class);
        $session->start();
    }

    private function makeSettingsManager(): SettingsManager
    {
        return new class extends SettingsManager {
            public function __construct()
            {
            }

            public function getAllSettings(string $fileName): array
            {
                return match (strtolower($fileName)) {
                    'encryption' => [
                        'DRIVER' => 'openssl',
                        'OPENSSL_CIPHER' => 'AES-256-CBC',
                        'KEY' => 'base64:' . base64_encode(str_repeat('a', 32)),
                        'OPENSSL_KEY' => 'base64:' . base64_encode(str_repeat('b', 32)),
                    ],
                    default => [],
                };
            }
        };
    }
}
