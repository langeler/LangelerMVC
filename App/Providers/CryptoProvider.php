<?php

namespace App\Providers;

use App\Core\Container;
use App\Drivers\Crypto\{
    SodiumCrypto,
    OpenSSLCrypto
};
use App\Exceptions\ContainerException;

/**
 * CryptoProvider Class
 *
 * This class extends the `Container` to provide cryptographic-related services.
 * It dynamically maps cryptographic drivers and supports resolution based on configuration.
 */
class CryptoProvider extends Container
{
    /**
     * A mapping of crypto driver aliases to their fully qualified class names.
     *
     * @var array<string, string>
     */
    protected readonly array $cryptoMap;

    /**
     * Constructor for CryptoProvider.
     *
     * Initializes the crypto map with supported drivers.
     */
    public function __construct()
    {
        $this->cryptoMap = [
            'sodium' => SodiumCrypto::class,
            'openssl' => OpenSSLCrypto::class,
        ];
    }

    /**
     * Registers cryptographic services in the container.
     *
     * Maps crypto drivers to aliases and registers them as lazy singletons.
     *
     * @return void
     * @throws ContainerException If an error occurs during registration.
     */
    public function registerServices(): void
    {
        $this->wrapInTry(
            fn() => (!$this->isArray($this->cryptoMap) || $this->isEmpty($this->cryptoMap))
                ? throw new ContainerException("The crypto map must be a non-empty array of aliases.")
                : $this->walk(
                    $this->cryptoMap,
                    fn($class, $alias) => [
                        $this->registerAlias($alias, $class),
                        $this->registerLazy($class, fn() => $this->resolveInstance($class))
                    ]
                ),
            new ContainerException("Error registering cryptographic services.")
        );
    }

    /**
     * Retrieves the appropriate crypto driver based on the provided configuration.
     *
     * @param array $cryptoSettings Configuration array specifying the crypto driver.
     * @return object The resolved crypto driver instance.
     * @throws ContainerException If the specified crypto driver is invalid or unsupported.
     */
    public function getCryptoDriver(array $cryptoSettings): object
    {
        return $this->wrapInTry(
            fn() => $this->getInstance(
                $this->cryptoMap[$cryptoSettings['DRIVER']
                    ?? throw new ContainerException("Crypto driver alias is missing or invalid.")]
            ),
            new ContainerException("Error retrieving crypto driver.")
        );
    }
}