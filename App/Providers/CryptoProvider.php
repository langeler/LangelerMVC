<?php

namespace App\Providers;

use App\Core\Container;
use App\Drivers\Cryptography\{
    SodiumCrypto,
    OpenSSLCrypto
};
use App\Exceptions\ContainerException;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\Patterns\PatternTrait;

/**
 * CryptoProvider Class
 *
 * This class extends the `Container` to provide cryptographic-related services.
 * It dynamically maps cryptographic drivers and supports resolution based on configuration.
 */
class CryptoProvider extends Container
{
    use ManipulationTrait, PatternTrait;

    /**
     * A mapping of crypto driver aliases to their fully qualified class names.
     *
     * @var array<string, string>
     */
    protected readonly array $cryptoMap;
    private bool $servicesRegistered = false;

    /**
     * Constructor for CryptoProvider.
     *
     * Initializes the crypto map with supported drivers.
     */
    public function __construct()
    {
        parent::__construct();

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
        if ($this->servicesRegistered) {
            return;
        }

        $this->wrapInTry(
            function (): void {
                if (!$this->isArray($this->cryptoMap) || $this->isEmpty($this->cryptoMap)) {
                    throw new ContainerException("The crypto map must be a non-empty array of aliases.");
                }

                foreach ($this->cryptoMap as $alias => $class) {
                    $this->registerAlias($alias, $class);
                    $this->registerLazy($class, fn() => $this->registerInstance($class));
                }

                $this->servicesRegistered = true;
            },
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
            function () use ($cryptoSettings): object {
                $driver = $this->toLower($this->trimString((string) ($this->replaceByPattern('/\s+#.*$/', '', (string) ($cryptoSettings['DRIVER'] ?? '')) ?? '')));

                return $this->getInstance(
                    $this->cryptoMap[$driver
                        ?: throw new ContainerException("Crypto driver alias is missing or invalid.")]
                    ?? throw new ContainerException("Unsupported crypto driver alias: {$driver}")
                );
            },
            new ContainerException("Error retrieving crypto driver.")
        );
    }
}
