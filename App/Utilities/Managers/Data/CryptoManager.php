<?php

namespace App\Utilities\Managers\Data;

use App\Providers\CryptoProvider;
use App\Utilities\Managers\SettingsManager;
use ReflectionMethod;

class CryptoManager
{
    public readonly array $cryptoSettings;
    public object $cryptoDriver;

    public function __construct(
        protected CryptoProvider $cryptoProvider,
        protected SettingsManager $settingsManager,
        ?object $cryptoDriver = null
    ) {
        $this->cryptoProvider->registerServices();
        $this->cryptoSettings = $this->settingsManager->getAllSettings('ENCRYPTION');
        $this->cryptoDriver = $cryptoDriver ?? $this->resolveCryptoDriver();
    }

    public function encrypt(string $type, mixed ...$args): mixed
    {
        return ($this->cryptoDriver->Encryptor($type))(...$args);
    }

    public function decrypt(string $type, mixed ...$args): mixed
    {
        return ($this->cryptoDriver->Decryptor($type))(...$args);
    }

    public function generateRandom(string $type, mixed ...$args): mixed
    {
        $method = new ReflectionMethod($this->cryptoDriver, 'RandomGenerator');
        $parameterCount = $method->getNumberOfParameters();

        if ($parameterCount > 1 && count($args) === 1 && is_int($args[0])) {
            return ($this->cryptoDriver->RandomGenerator($type, $args[0]))();
        }

        return ($this->cryptoDriver->RandomGenerator($type))(...$args);
    }

    public function hash(string $type, mixed ...$args): mixed
    {
        return ($this->cryptoDriver->Hasher($type))(...$args);
    }

    public function memory(string $action, mixed ...$args): mixed
    {
        return ($this->cryptoDriver->MemoryHandler($action))(...$args);
    }

    public function convert(string $type, mixed ...$args): mixed
    {
        return ($this->cryptoDriver->DataConverter($type))(...$args);
    }

    protected function resolveCryptoDriver(): object
    {
        $driver = strtolower(trim((string) preg_replace(
            '/\s+#.*$/',
            '',
            (string) ($this->cryptoSettings['DRIVER'] ?? $this->cryptoSettings['TYPE'] ?? 'openssl')
        )));

        return $this->cryptoProvider->getCryptoDriver([
            'DRIVER' => $driver,
        ]);
    }
}
