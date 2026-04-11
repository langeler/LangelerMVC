<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Support;

use App\Contracts\Support\PaymentDriverInterface;
use App\Contracts\Support\PaymentManagerInterface;
use App\Core\Config;
use App\Providers\PaymentProvider;
use App\Support\Payments\PaymentIntent;
use App\Support\Payments\PaymentResult;
use App\Utilities\Traits\ManipulationTrait;

class PaymentManager implements PaymentManagerInterface
{
    use ManipulationTrait {
        ManipulationTrait::toLower as private toLowerString;
    }

    private ?PaymentDriverInterface $driver = null;

    public function __construct(
        private readonly Config $config,
        private readonly PaymentProvider $provider
    ) {
        $this->provider->registerServices();
    }

    public function driverName(): string
    {
        return $this->toLowerString((string) $this->config->get('payment', 'DRIVER', 'testing'));
    }

    /**
     * @return list<string>
     */
    public function availableDrivers(): array
    {
        return $this->provider->getSupportedDrivers();
    }

    public function capabilities(): array
    {
        return $this->driver()->capabilities();
    }

    public function supports(string $feature): bool
    {
        return $this->driver()->supports($feature);
    }

    public function createIntent(int $amount, ?string $currency = null, string $description = '', array $metadata = []): PaymentIntent
    {
        return new PaymentIntent(
            $amount,
            $currency ?? (string) $this->config->get('payment', 'CURRENCY', 'SEK'),
            $description,
            $metadata
        );
    }

    public function authorize(PaymentIntent $intent): PaymentResult
    {
        return $this->driver()->authorize($intent);
    }

    public function capture(PaymentIntent $intent, ?int $amount = null): PaymentResult
    {
        return $this->driver()->capture($intent, $amount);
    }

    public function cancel(PaymentIntent $intent, ?string $reason = null): PaymentResult
    {
        return $this->driver()->cancel($intent, $reason);
    }

    public function refund(PaymentIntent $intent, ?int $amount = null, ?string $reason = null): PaymentResult
    {
        return $this->driver()->refund($intent, $amount, $reason);
    }

    private function driver(): PaymentDriverInterface
    {
        if ($this->driver instanceof PaymentDriverInterface) {
            return $this->driver;
        }

        $this->driver = $this->provider->getPaymentDriver([
            'DRIVER' => $this->driverName(),
        ]);

        return $this->driver;
    }
}
