<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Support\CarrierAdapterInterface;
use App\Core\Container;
use App\Drivers\Shipping\AirmeeCarrierAdapter;
use App\Drivers\Shipping\BringCarrierAdapter;
use App\Drivers\Shipping\BudbeeCarrierAdapter;
use App\Drivers\Shipping\DhlCarrierAdapter;
use App\Drivers\Shipping\EarlyBirdCarrierAdapter;
use App\Drivers\Shipping\InstaboxCarrierAdapter;
use App\Drivers\Shipping\PostNordCarrierAdapter;
use App\Drivers\Shipping\SchenkerCarrierAdapter;
use App\Drivers\Shipping\UpsCarrierAdapter;
use App\Exceptions\ContainerException;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\Patterns\PatternTrait;

class ShippingProvider extends Container
{
    use ManipulationTrait, PatternTrait;

    /**
     * @var array<string, class-string>
     */
    private array $adapterMap;

    private bool $servicesRegistered = false;

    public function __construct()
    {
        parent::__construct();

        $this->adapterMap = [
            'postnord' => PostNordCarrierAdapter::class,
            'instabox' => InstaboxCarrierAdapter::class,
            'budbee' => BudbeeCarrierAdapter::class,
            'bring' => BringCarrierAdapter::class,
            'dhl' => DhlCarrierAdapter::class,
            'schenker' => SchenkerCarrierAdapter::class,
            'earlybird' => EarlyBirdCarrierAdapter::class,
            'airmee' => AirmeeCarrierAdapter::class,
            'ups' => UpsCarrierAdapter::class,
        ];
    }

    public function registerServices(): void
    {
        if ($this->servicesRegistered) {
            return;
        }

        foreach ($this->adapterMap as $alias => $class) {
            $this->registerAlias($alias, $class);
            $this->registerLazy($class, fn() => $this->registerInstance($class));
        }

        $this->servicesRegistered = true;
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function getCarrierAdapter(string $carrierCode, array $settings = []): CarrierAdapterInterface
    {
        $carrierCode = $this->normalizeAlias($carrierCode);
        $class = $this->adapterMap[$carrierCode] ?? throw new ContainerException(sprintf('Unsupported carrier adapter [%s].', $carrierCode));
        $instance = $this->getInstance($class);

        if (!$instance instanceof CarrierAdapterInterface) {
            throw new ContainerException(sprintf('Resolved carrier adapter [%s] does not implement the carrier adapter contract.', $carrierCode));
        }

        return $instance->configure(array_merge($settings, ['CODE' => $carrierCode]));
    }

    /**
     * @return list<string>
     */
    public function getSupportedCarriers(): array
    {
        return array_keys($this->adapterMap);
    }

    /**
     * @param class-string $class
     */
    public function extendAdapter(string $carrierCode, string $class): void
    {
        $carrierCode = $this->normalizeAlias($carrierCode);
        $this->adapterMap[$carrierCode] = $class;

        if ($this->servicesRegistered) {
            $this->registerAlias($carrierCode, $class);
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
