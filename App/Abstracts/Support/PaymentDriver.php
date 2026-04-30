<?php

declare(strict_types=1);

namespace App\Abstracts\Support;

use App\Contracts\Support\PaymentDriverInterface;
use App\Exceptions\Support\PaymentException;
use App\Support\Payments\PaymentFlow;
use App\Support\Payments\PaymentIntent;
use App\Support\Payments\PaymentMethod;
use App\Support\Payments\PaymentResult;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\ConversionTrait;
use App\Utilities\Traits\ManipulationTrait;

abstract class PaymentDriver implements PaymentDriverInterface
{
    use ArrayTrait;
    use ConversionTrait;
    use ManipulationTrait {
        ManipulationTrait::toLower as protected toLowerString;
        ManipulationTrait::trimString as protected trimStringValue;
    }

    /**
     * @var array<string, mixed>
     */
    protected array $settings = [];

    /**
     * @param array<string, mixed> $settings
     */
    public function configure(array $settings): static
    {
        $this->settings = $settings;

        return $this;
    }

    public function capabilities(): array
    {
        $readiness = $this->readiness();

        return array_merge([
            'label' => $this->displayName(),
            'mode' => $this->mode(),
            'docs_url' => $this->docsUrl(),
            'regions' => $this->regions(),
            'methods' => $this->supportedMethods(),
            'flows' => $this->supportedFlows(),
            'required_settings' => $this->requiredSettings(),
            'missing_required_settings' => $readiness['missing_required_settings'],
            'live_ready' => $readiness['live_ready'],
            'reference_mode' => $this->mode() === 'reference',
            'webhook' => false,
            'idempotency' => false,
            'partial_capture' => false,
            'partial_refund' => false,
            'redirect' => false,
            'customer_action' => false,
            'external_gateway' => true,
        ], $this->driverCapabilities());
    }

    public function supports(string $feature): bool
    {
        $value = $this->capabilities();

        foreach (explode('.', $this->trimStringValue($feature)) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return false;
            }

            $value = $value[$segment];
        }

        return $value === true;
    }

    public function readiness(): array
    {
        $missing = [];

        if ($this->isLiveMode()) {
            foreach ($this->requiredSettings() as $key) {
                $value = $this->setting($key);

                if ($value === null || (is_string($value) && $this->trimStringValue($value) === '')) {
                    $missing[] = $key;
                }
            }
        }

        return [
            'driver' => $this->driverName(),
            'mode' => $this->mode(),
            'live_ready' => !$this->isLiveMode() || $missing === [],
            'missing_required_settings' => $missing,
        ];
    }

    public function supportedMethods(): array
    {
        return $this->normalizeStringList(
            $this->setting('METHODS', $this->defaultMethods())
        );
    }

    public function supportedFlows(): array
    {
        return $this->normalizeStringList(
            $this->setting('FLOWS', $this->defaultFlows())
        );
    }

    public function supportsMethod(PaymentMethod|string $method): bool
    {
        return in_array(
            $method instanceof PaymentMethod ? $method->value : $this->normalizeName((string) $method),
            $this->supportedMethods(),
            true
        );
    }

    public function supportsFlow(PaymentFlow|string $flow): bool
    {
        return in_array(
            $flow instanceof PaymentFlow ? $flow->value : $this->normalizeName((string) $flow),
            $this->supportedFlows(),
            true
        );
    }

    protected function displayName(): string
    {
        return (string) $this->setting('LABEL', ucfirst($this->driverName()));
    }

    protected function docsUrl(): ?string
    {
        $url = $this->trimStringValue((string) $this->setting('DOCS_URL', ''));

        return $url !== '' ? $url : null;
    }

    /**
     * @return list<string>
     */
    protected function regions(): array
    {
        $regions = $this->normalizeStringList($this->setting('REGIONS', ['GLOBAL']));

        return $regions === [] ? ['GLOBAL'] : $regions;
    }

    /**
     * @return list<string>
     */
    protected function requiredSettings(): array
    {
        return [];
    }

    protected function mode(): string
    {
        $mode = $this->normalizeName((string) $this->setting('MODE', 'reference'));

        return in_array($mode, ['live', 'reference'], true) ? $mode : 'reference';
    }

    protected function isLiveMode(): bool
    {
        return $this->mode() === 'live';
    }

    /**
     * @return array<string, mixed>
     */
    protected function driverCapabilities(): array
    {
        return [];
    }

    /**
     * @return list<string>
     */
    abstract protected function defaultMethods(): array;

    /**
     * @return list<string>
     */
    abstract protected function defaultFlows(): array;

    protected function referenceIntent(
        PaymentIntent $intent,
        string $status,
        array $nextAction = [],
        bool $customerActionRequired = false,
        ?int $authorizedAmount = null,
        ?int $capturedAmount = null,
        ?int $refundedAmount = null
    ): PaymentIntent {
        [$reference, $providerReference, $externalReference, $webhookReference] = $this->referenceSet($intent);

        return $intent
            ->withDriver($this->driverName())
            ->withReferences($reference, $providerReference, $externalReference, $webhookReference)
            ->withNextAction($nextAction, $customerActionRequired)
            ->withTotals(
                $authorizedAmount,
                $capturedAmount,
                $refundedAmount,
                $status
            );
    }

    protected function unsupportedResult(PaymentIntent $intent, string $action, ?string $message = null): PaymentResult
    {
        return new PaymentResult(
            false,
            $action,
            $intent->withDriver($this->driverName()),
            $this->driverName(),
            $message ?? sprintf('Payment driver [%s] does not support [%s].', $this->driverName(), $action),
            $intent->status
        );
    }

    /**
     * @return array{0:string,1:string,2:string,3:string}
     */
    protected function referenceSet(PaymentIntent $intent): array
    {
        $seed = implode('|', [
            $this->driverName(),
            (string) $intent->amount,
            $intent->currency,
            $intent->description,
            $intent->method,
            $intent->flow,
            $intent->idempotencyKey ?? $intent->reference ?? 'reference',
        ]);

        $suffix = substr(hash('sha256', $seed), 0, 16);

        return [
            $intent->reference ?? $this->driverName() . '_pay_' . $suffix,
            $intent->providerReference ?? $this->driverName() . '_provider_' . $suffix,
            $intent->externalReference ?? $this->driverName() . '_external_' . $suffix,
            $intent->webhookReference ?? $this->driverName() . '_wh_' . $suffix,
        ];
    }

    protected function paymentMetadata(PaymentIntent $intent, string $key, mixed $default = null): mixed
    {
        $metadata = $intent->metadata;

        if (!is_array($metadata)) {
            return $default;
        }

        $current = $metadata;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($current)) {
                return $default;
            }

            $found = false;

            foreach ($current as $candidateKey => $candidateValue) {
                if (strcasecmp((string) $candidateKey, $segment) === 0) {
                    $current = $candidateValue;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                return $default;
            }
        }

        return $current;
    }

    protected function setting(string $key, mixed $default = null): mixed
    {
        $current = $this->settings;

        foreach (explode('.', $key) as $segment) {
            if (!is_array($current)) {
                return $default;
            }

            $found = false;

            foreach ($current as $candidateKey => $candidateValue) {
                if (strcasecmp((string) $candidateKey, $segment) === 0) {
                    $current = $candidateValue;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                return $default;
            }
        }

        return $current;
    }

    /**
     * @param list<string> $keys
     */
    protected function ensureLiveRequirements(array $keys): void
    {
        if (!$this->isLiveMode()) {
            return;
        }

        $missing = [];

        foreach ($keys as $key) {
            $value = $this->setting($key);

            if ($value === null) {
                $missing[] = $key;
                continue;
            }

            if (is_string($value) && $this->trimStringValue($value) === '') {
                $missing[] = $key;
            }
        }

        if ($missing !== []) {
            throw new PaymentException(sprintf(
                'Payment driver [%s] is missing required configuration: %s.',
                $this->driverName(),
                implode(', ', $missing)
            ));
        }
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed>|null $json
     * @param array<string, mixed> $options
     * @return array{status:int,headers:array<string, string>,body:string,json:array<string, mixed>}
     */
    protected function requestJson(string $method, string $url, array $headers = [], ?array $json = null, array $options = []): array
    {
        $body = $json === null
            ? null
            : $this->toJson($json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $response = $this->request($method, $url, array_merge([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ], $headers), $body, $options);

        try {
            $decoded = $response['body'] !== ''
                ? $this->fromJson($response['body'], true, 512, JSON_THROW_ON_ERROR)
                : [];
        } catch (\JsonException) {
            $decoded = [];
        }

        $response['json'] = is_array($decoded) ? $decoded : [];

        return $response;
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $options
     * @return array{status:int,headers:array<string, string>,body:string,json:array<string, mixed>}
     */
    protected function request(string $method, string $url, array $headers = [], ?string $body = null, array $options = []): array
    {
        if (function_exists('curl_init')) {
            return $this->curlRequest($method, $url, $headers, $body, $options);
        }

        return $this->streamRequest($method, $url, $headers, $body, $options);
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $options
     * @return array{status:int,headers:array<string, string>,body:string,json:array<string, mixed>}
     */
    protected function curlRequest(string $method, string $url, array $headers, ?string $body, array $options): array
    {
        $handle = curl_init($url);

        if ($handle === false) {
            throw new PaymentException(sprintf('Unable to initialize cURL for payment driver [%s].', $this->driverName()));
        }

        $responseHeaders = [];

        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => (int) ($options['timeout'] ?? 30),
            CURLOPT_CONNECTTIMEOUT => (int) ($options['connect_timeout'] ?? 10),
            CURLOPT_HTTPHEADER => $this->normalizeHeaders($headers),
            CURLOPT_HEADERFUNCTION => function ($curl, string $line) use (&$responseHeaders): int {
                $length = strlen($line);
                $header = trim($line);

                if ($header === '' || !str_contains($header, ':')) {
                    return $length;
                }

                [$name, $value] = explode(':', $header, 2);
                $responseHeaders[trim($name)] = trim($value);

                return $length;
            },
        ]);

        if ($body !== null) {
            curl_setopt($handle, CURLOPT_POSTFIELDS, $body);
        }

        if (isset($options['basic_auth']) && is_array($options['basic_auth'])) {
            $user = (string) ($options['basic_auth']['username'] ?? '');
            $password = (string) ($options['basic_auth']['password'] ?? '');
            curl_setopt($handle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($handle, CURLOPT_USERPWD, $user . ':' . $password);
        }

        if (isset($options['ssl']) && is_array($options['ssl'])) {
            $ssl = $options['ssl'];

            if (!empty($ssl['cert'])) {
                curl_setopt($handle, CURLOPT_SSLCERT, (string) $ssl['cert']);
            }

            if (!empty($ssl['key'])) {
                curl_setopt($handle, CURLOPT_SSLKEY, (string) $ssl['key']);
            }

            if (!empty($ssl['passphrase'])) {
                curl_setopt($handle, CURLOPT_KEYPASSWD, (string) $ssl['passphrase']);
            }
        }

        $bodyResponse = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($bodyResponse === false) {
            throw new PaymentException(sprintf(
                'Payment driver [%s] HTTP request failed: %s',
                $this->driverName(),
                $error !== '' ? $error : 'unknown cURL error'
            ));
        }

        return [
            'status' => $status,
            'headers' => $responseHeaders,
            'body' => (string) $bodyResponse,
            'json' => [],
        ];
    }

    /**
     * @param array<string, string> $headers
     * @param array<string, mixed> $options
     * @return array{status:int,headers:array<string, string>,body:string,json:array<string, mixed>}
     */
    protected function streamRequest(string $method, string $url, array $headers, ?string $body, array $options): array
    {
        $headerLines = $this->normalizeHeaders($headers);
        $ssl = isset($options['ssl']) && is_array($options['ssl']) ? $options['ssl'] : [];
        $context = stream_context_create([
            'http' => [
                'method' => strtoupper($method),
                'header' => implode("\r\n", $headerLines),
                'content' => $body ?? '',
                'ignore_errors' => true,
                'timeout' => (int) ($options['timeout'] ?? 30),
            ],
            'ssl' => array_filter([
                'local_cert' => $ssl['cert'] ?? null,
                'local_pk' => $ssl['key'] ?? null,
                'passphrase' => $ssl['passphrase'] ?? null,
            ], static fn(mixed $value): bool => $value !== null && $value !== ''),
        ]);

        $responseBody = @file_get_contents($url, false, $context);

        if ($responseBody === false) {
            throw new PaymentException(sprintf(
                'Payment driver [%s] stream request failed for [%s].',
                $this->driverName(),
                $url
            ));
        }

        $rawHeaders = $http_response_header ?? [];
        $status = 200;
        $responseHeaders = [];

        foreach ($rawHeaders as $index => $headerLine) {
            if ($index === 0 && preg_match('/\s(\d{3})\s/', $headerLine, $matches) === 1) {
                $status = (int) $matches[1];
                continue;
            }

            if (!str_contains($headerLine, ':')) {
                continue;
            }

            [$name, $value] = explode(':', $headerLine, 2);
            $responseHeaders[trim($name)] = trim($value);
        }

        return [
            'status' => $status,
            'headers' => $responseHeaders,
            'body' => (string) $responseBody,
            'json' => [],
        ];
    }

    /**
     * @param array<string, string> $headers
     * @return list<string>
     */
    protected function normalizeHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $name => $value) {
            $normalized[] = trim((string) $name) . ': ' . trim((string) $value);
        }

        return $normalized;
    }

    /**
     * @param mixed $values
     * @return list<string>
     */
    protected function normalizeStringList(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $normalized = [];

        foreach ($values as $value) {
            $string = $this->normalizeName((string) $value);

            if ($string === '' || in_array($string, $normalized, true)) {
                continue;
            }

            $normalized[] = $string;
        }

        return $normalized;
    }

    protected function normalizeName(string $value): string
    {
        return $this->toLowerString($this->trimStringValue($value));
    }

    /**
     * @param array<string, string> $headers
     */
    protected function requireSuccessfulResponse(
        array $response,
        array $acceptedStatuses,
        string $action
    ): void {
        if (in_array((int) ($response['status'] ?? 0), $acceptedStatuses, true)) {
            return;
        }

        throw new PaymentException(sprintf(
            'Payment driver [%s] %s request failed with HTTP %d.',
            $this->driverName(),
            $action,
            (int) ($response['status'] ?? 0)
        ));
    }
}
