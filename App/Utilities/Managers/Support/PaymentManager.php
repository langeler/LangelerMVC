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
                'missing_required_settings' => is_array($capabilities['missing_required_settings'] ?? null)
                    ? array_values($capabilities['missing_required_settings'])
                    : [],
                'live_ready' => (bool) ($capabilities['live_ready'] ?? true),
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

    /**
     * @return array<string, mixed>
     */
    public function webhookSettings(?string $driver = null): array
    {
        $resolvedDriver = $this->resolveDriverName($driver, true);
        $global = $this->config->get('payment', 'WEBHOOKS', []);
        $global = is_array($global) ? $global : [];
        $driverSettings = $this->driverSettings($resolvedDriver);
        $driverWebhook = $driverSettings['WEBHOOKS'] ?? ($driverSettings['WEBHOOK'] ?? []);
        $driverWebhook = is_array($driverWebhook) ? $driverWebhook : [];
        $secrets = is_array($global['SECRETS'] ?? null) ? $global['SECRETS'] : [];
        $secret = $driverWebhook['SECRET']
            ?? $driverSettings['WEBHOOK_SECRET']
            ?? $secrets[$resolvedDriver]
            ?? $global['SECRET']
            ?? '';

        return array_merge([
            'ENABLED' => true,
            'REQUIRE_SIGNATURE' => true,
            'SIGNATURE_HEADER' => 'X-Langeler-Signature',
            'EVENT_ID_HEADER' => 'X-Langeler-Event',
            'TIMESTAMP_HEADER' => 'X-Langeler-Timestamp',
            'TOLERANCE_SECONDS' => 300,
        ], $global, $driverWebhook, [
            'SECRET' => is_scalar($secret) ? trim((string) $secret) : '',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function canonicalWebhookPayload(array $payload): string
    {
        foreach (array_keys($payload) as $key) {
            if (str_starts_with((string) $key, '_webhook_')) {
                unset($payload[$key]);
            }
        }

        $payload = $this->sortPayloadKeys($payload);

        return json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    public function webhookPayloadSignature(string $driver, string $payload): string
    {
        $settings = $this->webhookSettings($driver);
        $secret = trim((string) ($settings['SECRET'] ?? ''));

        return $secret !== '' ? hash_hmac('sha256', $payload, $secret) : '';
    }

    /**
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    public function verifyWebhookSignature(
        string $driver,
        string $payload,
        array $headers = [],
        ?string $providedSignature = null
    ): array {
        $settings = $this->webhookSettings($driver);

        if (!(bool) ($settings['ENABLED'] ?? true)) {
            return [
                'accepted' => false,
                'verified' => false,
                'required' => false,
                'status' => 403,
                'message' => 'Payment webhooks are disabled.',
            ];
        }

        if (!$this->driver($driver, true)->supports('webhook')) {
            return [
                'accepted' => false,
                'verified' => false,
                'required' => false,
                'status' => 422,
                'message' => sprintf('Payment driver [%s] does not support webhooks.', $driver),
            ];
        }

        $secret = trim((string) ($settings['SECRET'] ?? ''));
        $required = (bool) ($settings['REQUIRE_SIGNATURE'] ?? true);

        if ($secret === '') {
            return [
                'accepted' => !$required,
                'verified' => false,
                'required' => $required,
                'status' => $required ? 401 : 200,
                'message' => $required
                    ? 'Payment webhook signature secret is not configured.'
                    : 'Payment webhook accepted without signature verification because no secret is configured.',
                'secret_configured' => false,
            ];
        }

        $signatureHeader = trim((string) ($settings['SIGNATURE_HEADER'] ?? 'X-Langeler-Signature'));
        $signature = trim((string) ($providedSignature ?? $this->headerValue($headers, $signatureHeader, '')));

        if ($signature === '') {
            return [
                'accepted' => !$required,
                'verified' => false,
                'required' => $required,
                'status' => $required ? 401 : 200,
                'message' => $required
                    ? 'Payment webhook signature header is missing.'
                    : 'Payment webhook accepted without a signature header because verification is optional.',
                'secret_configured' => true,
            ];
        }

        $timestampCheck = $this->verifyWebhookTimestamp($settings, $headers);

        if (!$timestampCheck['valid']) {
            return [
                'accepted' => false,
                'verified' => false,
                'required' => $required,
                'status' => 401,
                'message' => $timestampCheck['message'],
                'secret_configured' => true,
            ];
        }

        $signature = $this->normalizeSignatureValue($signature);
        $expected = hash_hmac('sha256', $payload, $secret);
        $verified = hash_equals($expected, $signature);

        return [
            'accepted' => $verified || !$required,
            'verified' => $verified,
            'required' => $required,
            'status' => $verified || !$required ? 200 : 401,
            'message' => $verified
                ? 'Payment webhook signature verified.'
                : 'Payment webhook signature verification failed.',
            'secret_configured' => true,
            'signature_header' => $signatureHeader,
        ];
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

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function sortPayloadKeys(array $payload): array
    {
        ksort($payload);

        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->sortPayloadKeys($value);
            }
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $headers
     */
    private function headerValue(array $headers, string $name, mixed $default = null): mixed
    {
        $normalizedName = $this->toLowerString($this->trimStringValue($name));

        foreach ($headers as $key => $value) {
            if ($this->toLowerString($this->trimStringValue((string) $key)) === $normalizedName) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $headers
     * @return array{valid:bool,message:string}
     */
    private function verifyWebhookTimestamp(array $settings, array $headers): array
    {
        $tolerance = max(0, (int) ($settings['TOLERANCE_SECONDS'] ?? 0));

        if ($tolerance === 0) {
            return ['valid' => true, 'message' => 'Timestamp tolerance disabled.'];
        }

        $header = trim((string) ($settings['TIMESTAMP_HEADER'] ?? 'X-Langeler-Timestamp'));
        $timestamp = trim((string) $this->headerValue($headers, $header, ''));

        if ($timestamp === '') {
            return ['valid' => true, 'message' => 'Timestamp header not provided.'];
        }

        $epoch = ctype_digit($timestamp) ? (int) $timestamp : strtotime($timestamp);

        if ($epoch <= 0) {
            return ['valid' => false, 'message' => 'Payment webhook timestamp is invalid.'];
        }

        if (abs(time() - $epoch) > $tolerance) {
            return ['valid' => false, 'message' => 'Payment webhook timestamp is outside the configured tolerance.'];
        }

        return ['valid' => true, 'message' => 'Payment webhook timestamp accepted.'];
    }

    private function normalizeSignatureValue(string $signature): string
    {
        $signature = trim($signature);

        if (str_contains($signature, ',')) {
            $parts = array_map('trim', explode(',', $signature));
            $signature = (string) ($parts[0] ?? $signature);
        }

        if (str_starts_with($this->toLowerString($signature), 'sha256=')) {
            return substr($signature, 7);
        }

        return $signature;
    }
}
