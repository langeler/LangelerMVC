<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Support;

use App\Contracts\Support\PaymentDriverInterface;
use App\Contracts\Support\PaymentManagerInterface;
use App\Core\Config;
use App\Providers\PaymentProvider;
use App\Support\Payments\PaymentFlow;
use App\Support\Payments\PaymentIntent;
use App\Support\Payments\PaymentMethod;
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

    public function supportedMethods(): array
    {
        return $this->driver()->supportedMethods();
    }

    public function supportedFlows(): array
    {
        return $this->driver()->supportedFlows();
    }

    public function supportsMethod(PaymentMethod|string $method): bool
    {
        return $this->driver()->supportsMethod($method);
    }

    public function supportsFlow(PaymentFlow|string $flow): bool
    {
        return $this->driver()->supportsFlow($flow);
    }

    public function createIntent(
        int $amount,
        ?string $currency = null,
        string $description = '',
        array $metadata = [],
        PaymentMethod|string|null $method = null,
        PaymentFlow|string|null $flow = null,
        ?string $idempotencyKey = null
    ): PaymentIntent {
        $resolvedMethod = PaymentMethod::fromMixed($method ?? (string) $this->config->get('payment', 'DEFAULT_METHOD', PaymentMethod::default()->value));
        $resolvedFlow = PaymentFlow::fromMixed($flow ?? (string) $this->config->get('payment', 'DEFAULT_FLOW', PaymentFlow::default()->value));

        return new PaymentIntent(
            $amount,
            $currency ?? (string) $this->config->get('payment', 'CURRENCY', 'SEK'),
            $description,
            $metadata,
            $resolvedMethod->value,
            $resolvedFlow->value,
            null,
            null,
            null,
            $idempotencyKey
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

    public function reconcile(PaymentIntent $intent, array $payload = []): PaymentResult
    {
        return $this->driver()->reconcile($intent, $payload);
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
