<?php

declare(strict_types=1);

namespace App\Drivers\Session;

use App\Contracts\Session\SessionDriverInterface;
use App\Exceptions\SessionException;
use App\Utilities\Traits\ManipulationTrait;
use Redis;
use SessionHandler;

class RedisSessionDriver extends SessionHandler implements SessionDriverInterface
{
    use ManipulationTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    private ?Redis $redis = null;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly array $config = []
    ) {
    }

    public function driverName(): string
    {
        return 'redis';
    }

    public function capabilities(): array
    {
        return [
            'extension' => class_exists(Redis::class),
            'persistent' => true,
            'ttl' => true,
        ];
    }

    public function supports(string $feature): bool
    {
        return ($this->capabilities()[$feature] ?? null) === true;
    }

    public function open(string $savePath, string $sessionName): bool
    {
        $this->connect();

        return true;
    }

    public function close(): bool
    {
        if ($this->redis instanceof Redis) {
            $this->redis->close();
            $this->redis = null;
        }

        return true;
    }

    public function read(string $id): string|false
    {
        $value = $this->connect()->get($this->key($id));

        return is_string($value) ? $value : '';
    }

    public function write(string $id, string $data): bool
    {
        $ttl = (int) ($this->config['ttl'] ?? 1440);

        return $ttl > 0
            ? $this->connect()->setex($this->key($id), $ttl, $data)
            : $this->connect()->set($this->key($id), $data);
    }

    public function destroy(string $id): bool
    {
        return $this->connect()->del($this->key($id)) >= 0;
    }

    public function gc(int $max_lifetime): int|false
    {
        return 0;
    }

    private function connect(): Redis
    {
        if ($this->redis instanceof Redis) {
            return $this->redis;
        }

        if (!class_exists(Redis::class)) {
            throw new SessionException('Redis session driver requires the ext-redis PHP extension.');
        }

        $client = new Redis();
        $host = (string) ($this->config['host'] ?? '127.0.0.1');
        $port = (int) ($this->config['port'] ?? 6379);
        $timeout = (float) ($this->config['timeout'] ?? 0.0);
        $connected = $timeout > 0.0
            ? $client->connect($host, $port, $timeout)
            : $client->connect($host, $port);

        if (!$connected) {
            throw new SessionException('Unable to connect to the configured Redis session store.');
        }

        $password = (string) ($this->config['password'] ?? '');
        $database = (int) ($this->config['database'] ?? 0);

        if ($password !== '' && !$client->auth($password)) {
            throw new SessionException('Redis session authentication failed.');
        }

        if ($database > 0 && !$client->select($database)) {
            throw new SessionException('Unable to select the configured Redis session database.');
        }

        return $this->redis = $client;
    }

    private function key(string $id): string
    {
        $prefix = trim((string) ($this->config['prefix'] ?? 'langelermvc_session'), ':');

        return $prefix . ':' . trim($id);
    }
}
