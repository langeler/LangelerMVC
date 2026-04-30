<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Support\PaymentDriverInterface;
use App\Core\Container;
use App\Drivers\Payments\CardPaymentDriver;
use App\Drivers\Payments\CryptoPaymentDriver;
use App\Drivers\Payments\KlarnaPaymentDriver;
use App\Drivers\Payments\PayPalPaymentDriver;
use App\Drivers\Payments\QliroPaymentDriver;
use App\Drivers\Payments\SwishPaymentDriver;
use App\Drivers\Payments\TestingPaymentDriver;
use App\Drivers\Payments\WalleyPaymentDriver;
use App\Exceptions\ContainerException;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\Patterns\PatternTrait;

class PaymentProvider extends Container
{
    use ManipulationTrait, PatternTrait;

    /**
     * @var array<string, string>
     */
    private array $driverMap;

    private bool $servicesRegistered = false;

    public function __construct()
    {
        parent::__construct();

        $this->driverMap = [
            'testing' => TestingPaymentDriver::class,
            'card' => CardPaymentDriver::class,
            'crypto' => CryptoPaymentDriver::class,
            'paypal' => PayPalPaymentDriver::class,
            'klarna' => KlarnaPaymentDriver::class,
            'swish' => SwishPaymentDriver::class,
            'qliro' => QliroPaymentDriver::class,
            'walley' => WalleyPaymentDriver::class,
        ];
    }

    public function registerServices(): void
    {
        if ($this->servicesRegistered) {
            return;
        }

        foreach ($this->driverMap as $alias => $class) {
            $this->registerAlias($alias, $class);
            $this->registerLazy($class, fn() => $this->registerInstance($class));
        }

        $this->servicesRegistered = true;
    }

    public function getPaymentDriver(array $settings): PaymentDriverInterface
    {
        $driver = $this->normalizeAlias((string) ($settings['DRIVER'] ?? 'testing'));
        $class = $this->driverMap[$driver] ?? throw new ContainerException(sprintf('Unsupported payment driver [%s].', $driver));
        $instance = $this->getInstance($class);

        if (!$instance instanceof PaymentDriverInterface) {
            throw new ContainerException(sprintf('Resolved payment driver [%s] does not implement the payment contract.', $driver));
        }

        return $instance->configure($settings);
    }

    /**
     * @return list<string>
     */
    public function getSupportedDrivers(): array
    {
        return array_keys($this->driverMap);
    }

    public function extendDriver(string $alias, string $class): void
    {
        $alias = $this->normalizeAlias($alias);
        $this->driverMap[$alias] = $class;

        if ($this->servicesRegistered) {
            $this->registerAlias($alias, $class);
            $this->registerLazy($class, fn() => $this->registerInstance($class));
        }
    }

    private function normalizeAlias(string $name): string
    {
        return $this->toLower(
            $this->trimString((string) ($this->replaceByPattern('/\s+#.*$/', '', $name) ?? $name))
        );
    }
}
