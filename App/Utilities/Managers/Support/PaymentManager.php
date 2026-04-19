<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Support;

use App\Contracts\Support\PaymentDriverInterface;
use App\Contracts\Support\PaymentManagerInterface;
use App\Core\Config;
use App\Exceptions\Support\PaymentException;
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
        ManipulationTrait::trimString as private trimStringValue;
    }

    /**
     * @var array<string, PaymentDriverInterface>
     */
    private array $drivers = [];

    public function __construct(
        private readonly Config $config,
        private readonly PaymentProvider $provider
    ) {
        $this->provider->registerServices();
    }

    public function driverName(): string
    {
        return $this->normalizeDriverName((string) $this->config->get('payment', 'DRIVER', 'testing'));
    }

    public function availableDrivers(): array
    {
        $available = [];

        foreach ($this->provider->getSupportedDrivers() as $driver) {
            $settings = $this->driverSettings($driver);

            if ((bool) ($settings['ENABLED'] ?? false)) {
                $available[] = $driver;
            }
        }

        if ($available === []) {
            return ['testing'];
        }

        return array_values(array_unique($available));
    }

    public function driverCatalog(): array
    {
        $catalog = [];

        foreach ($this->availableDrivers() as $driver) {
            $capabilities = $this->capabilities($driver);

            $catalog[$driver] = [
                'driver' => $driver,
                'label' => (string) ($capabilities['label'] ?? ucfirst($driver)),
                'enabled' => true,
                'mode' => (string) ($capabilities['mode'] ?? 'reference'),
                'docs_url' => $capabilities['docs_url'] ?? null,
                'regions' => is_array($capabilities['regions'] ?? null) ? array_values($capabilities['regions']) : [],
                'methods' => $this->supportedMethods($driver),
                'flows' => $this->supportedFlows($driver),
                'required_settings' => is_array($capabilities['required_settings'] ?? null)
                    ? array_values($capabilities['required_settings'])
                    : [],
                'capabilities' => $capabilities,
            ];
        }

        return $catalog;
    }

    public function capabilities(?string $driver = null): array
    {
        $resolved = $this->driver($driver);

        return $resolved->capabilities();
    }

    public function supports(string $feature, ?string $driver = null): bool
    {
        return $this->driver($driver)->supports($feature);
    }

    public function supportedMethods(?string $driver = null): array
    {
        return $this->driver($driver)->supportedMethods();
    }

    public function supportedFlows(?string $driver = null): array
    {
        return $this->driver($driver)->supportedFlows();
    }

    public function supportsMethod(PaymentMethod|string $method, ?string $driver = null): bool
    {
        return $this->driver($driver)->supportsMethod($method);
    }

    public function supportsFlow(PaymentFlow|string $flow, ?string $driver = null): bool
    {
        return $this->driver($driver)->supportsFlow($flow);
    }

    public function createIntent(
        int $amount,
        ?string $currency = null,
        string $description = '',
        array $metadata = [],
        PaymentMethod|string|null $method = null,
        PaymentFlow|string|null $flow = null,
        ?string $idempotencyKey = null,
        ?string $driver = null
    ): PaymentIntent {
        $resolvedDriver = $this->resolveDriverName($driver);
        $defaultMethod = $this->defaultMethodFor($resolvedDriver);
        $defaultFlow = $this->defaultFlowFor($resolvedDriver);
        $resolvedMethod = PaymentMethod::fromMixed($method ?? $defaultMethod);
        $resolvedFlow = PaymentFlow::fromMixed($flow ?? $defaultFlow);

        if (!$this->supportsMethod($resolvedMethod, $resolvedDriver)) {
            $resolvedMethod = PaymentMethod::fromMixed($defaultMethod);
        }

        if (!$this->supportsFlow($resolvedFlow, $resolvedDriver)) {
            $resolvedFlow = PaymentFlow::fromMixed($defaultFlow);
        }

        return new PaymentIntent(
            $amount,
            $currency ?? (string) $this->config->get('payment', 'CURRENCY', 'SEK'),
            $description,
            $metadata,
            $resolvedDriver,
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
        $driver = $this->driver($intent->driver, true);

        return $driver->authorize($intent->withDriver($this->resolveDriverName($intent->driver, true)));
    }

    public function capture(PaymentIntent $intent, ?int $amount = null): PaymentResult
    {
        $driver = $this->driver($intent->driver, true);

        return $driver->capture($intent->withDriver($this->resolveDriverName($intent->driver, true)), $amount);
    }

    public function cancel(PaymentIntent $intent, ?string $reason = null): PaymentResult
    {
        $driver = $this->driver($intent->driver, true);

        return $driver->cancel($intent->withDriver($this->resolveDriverName($intent->driver, true)), $reason);
    }

    public function refund(PaymentIntent $intent, ?int $amount = null, ?string $reason = null): PaymentResult
    {
        $driver = $this->driver($intent->driver, true);

        return $driver->refund($intent->withDriver($this->resolveDriverName($intent->driver, true)), $amount, $reason);
    }

    public function reconcile(PaymentIntent $intent, array $payload = []): PaymentResult
    {
        $driver = $this->driver($intent->driver, true);

        return $driver->reconcile($intent->withDriver($this->resolveDriverName($intent->driver, true)), $payload);
    }

    private function defaultMethodFor(string $driver): string
    {
        $driverSettings = $this->driverSettings($driver);
        $configured = $this->normalizeDriverName((string) ($driverSettings['DEFAULT_METHOD'] ?? ''));

        if ($configured !== '' && $this->supportsMethod($configured, $driver)) {
            return $configured;
        }

        $configured = $this->normalizeDriverName((string) $this->config->get('payment', 'DEFAULT_METHOD', PaymentMethod::default()->value));

        if ($configured !== '' && $this->supportsMethod($configured, $driver)) {
            return $configured;
        }

        return $this->supportedMethods($driver)[0] ?? PaymentMethod::default()->value;
    }

    private function defaultFlowFor(string $driver): string
    {
        $driverSettings = $this->driverSettings($driver);
        $configured = $this->normalizeDriverName((string) ($driverSettings['DEFAULT_FLOW'] ?? ''));

        if ($configured !== '' && $this->supportsFlow($configured, $driver)) {
            return $configured;
        }

        $configured = $this->normalizeDriverName((string) $this->config->get('payment', 'DEFAULT_FLOW', PaymentFlow::default()->value));

        if ($configured !== '' && $this->supportsFlow($configured, $driver)) {
            return $configured;
        }

        return $this->supportedFlows($driver)[0] ?? PaymentFlow::default()->value;
    }

    /**
     * @return array<string, mixed>
     */
    private function driverSettings(string $driver): array
    {
        $drivers = $this->config->get('payment', 'DRIVERS', []);

        if (!is_array($drivers)) {
            return [];
        }

        foreach ($drivers as $candidate => $settings) {
            if ($this->normalizeDriverName((string) $candidate) !== $driver || !is_array($settings)) {
                continue;
            }

            return $settings;
        }

        return [];
    }

    private function driver(?string $driver = null, bool $allowDisabled = false): PaymentDriverInterface
    {
        $resolvedName = $this->resolveDriverName($driver, $allowDisabled);

        if (isset($this->drivers[$resolvedName])) {
            return $this->drivers[$resolvedName];
        }

        $this->drivers[$resolvedName] = $this->provider->getPaymentDriver(array_merge(
            $this->driverSettings($resolvedName),
            ['DRIVER' => $resolvedName]
        ));

        return $this->drivers[$resolvedName];
    }

    private function resolveDriverName(?string $driver = null, bool $allowDisabled = false): string
    {
        $resolved = $this->normalizeDriverName($driver ?? $this->driverName());

        if ($resolved === '') {
            $resolved = 'testing';
        }

        $supported = $this->provider->getSupportedDrivers();

        if (!in_array($resolved, $supported, true)) {
            throw new PaymentException(sprintf('Unsupported payment driver [%s].', $resolved));
        }

        if (!$allowDisabled && !in_array($resolved, $this->availableDrivers(), true)) {
            throw new PaymentException(sprintf('Payment driver [%s] is not enabled.', $resolved));
        }

        return $resolved;
    }

    private function normalizeDriverName(string $driver): string
    {
        return $this->toLowerString($this->trimStringValue($driver));
    }
}
