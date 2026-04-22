<?php

declare(strict_types=1);

namespace App\Core;

use App\Contracts\Http\ResponseInterface;
use App\Providers\CoreProvider;
use App\Contracts\Support\HealthManagerInterface;
use App\Utilities\Managers\Security\HttpSecurityManager;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Traits\{
    CheckerTrait,
    ConversionTrait,
    ErrorTrait,
    ExistenceCheckerTrait,
    Filters\FiltrationTrait,
    ManipulationTrait,
    TypeCheckerTrait
};
use JsonSerializable;
use Stringable;

/**
 * Core application runtime.
 *
 * Responsible for booting the framework, applying runtime policy from config,
 * dispatching the current request, and emitting the resolved response payload.
 */
class App
{
    use ErrorTrait, TypeCheckerTrait, ExistenceCheckerTrait, CheckerTrait, ManipulationTrait, ConversionTrait, FiltrationTrait;

    private bool $booted = false;
    private bool $maintenanceMode = false;
    private ?Config $config = null;
    private ?Router $router = null;
    private ?HttpSecurityManager $httpSecurity = null;
    private ?Session $session = null;

    public function __construct(
        protected CoreProvider $coreProvider,
        protected ErrorManager $errorManager
    ) {
    }

    /**
     * Boot the application once for the current request lifecycle.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->config = $this->resolveConfig();
        $this->router = $this->resolveRouter();
        $this->httpSecurity = $this->resolveHttpSecurity();

        $this->configureRuntime();
        $this->maintenanceMode = $this->isMaintenanceModeEnabled();
        $this->booted = true;
    }

    /**
     * Boot and handle the current request.
     */
    public function run(): void
    {
        $this->boot();

        if ($this->maintenanceMode) {
            $this->emitMaintenanceResponse();
            return;
        }

        $result = $this->dispatchCurrentRequest();

        $this->emit($result);
    }

    private function resolveConfig(): Config
    {
        $config = $this->wrapInTry(
            fn(): object => $this->coreProvider->getCoreService('config'),
            fn($caught) => $this->errorManager->resolveException(
                'app',
                'Failed to resolve Config service: ' . $caught->getMessage(),
                (int) $caught->getCode(),
                $caught
            )
        );

        if (!$config instanceof Config) {
            throw $this->errorManager->resolveException('app', 'Core service [config] must resolve to App\Core\Config.');
        }

        return $config;
    }

    private function resolveRouter(): Router
    {
        $router = $this->wrapInTry(
            fn(): object => $this->coreProvider->getCoreService('router'),
            fn($caught) => $this->errorManager->resolveException(
                'app',
                'Failed to resolve Router service: ' . $caught->getMessage(),
                (int) $caught->getCode(),
                $caught
            )
        );

        if (!$router instanceof Router) {
            throw $this->errorManager->resolveException('app', 'Core service [router] must resolve to App\Core\Router.');
        }

        return $router;
    }

    private function resolveHttpSecurity(): ?HttpSecurityManager
    {
        try {
            $resolved = $this->coreProvider->getCoreService('httpSecurity');
        } catch (\Throwable) {
            return null;
        }

        return $resolved instanceof HttpSecurityManager ? $resolved : null;
    }

    private function configureRuntime(): void
    {
        $debugEnabled = $this->normalizeBool($this->config?->get('app', 'DEBUG', false), false);
        $timezone = $this->normalizeTimezone($this->config?->get('app', 'TIMEZONE', 'UTC'));

        error_reporting(E_ALL);
        ini_set('display_errors', $debugEnabled ? '1' : '0');
        ini_set('display_startup_errors', $debugEnabled ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('default_charset', 'UTF-8');
        date_default_timezone_set($timezone);
    }

    private function dispatchCurrentRequest(): mixed
    {
        $frameworkHealth = $this->dispatchFrameworkHealthRequest();

        if ($frameworkHealth !== null) {
            return $frameworkHealth;
        }

        return $this->wrapInTry(
            fn(): mixed => $this->router?->dispatch(
                $this->resolveRequestUri(),
                $this->resolveRequestMethod()
            ),
            fn($caught) => $this->errorManager->resolveException(
                'app',
                'Application run failed: ' . $caught->getMessage(),
                (int) $caught->getCode(),
                $caught
            )
        );
    }

    private function emit(mixed $result): void
    {
        $this->wrapInTry(
            function () use ($result): void {
                if ($result instanceof ResponseInterface) {
                    $this->prepareFrameworkResponse($result);
                    $result->send();
                    return;
                }

                if ($this->isObject($result) && $this->methodExists($result, 'send')) {
                    $result->send();
                    return;
                }

                if ($result === null) {
                    return;
                }

                if ($this->isArray($result) || $result instanceof JsonSerializable) {
                    $this->emitJson($result);
                    return;
                }

                if ($this->isScalar($result) || $result instanceof Stringable) {
                    $this->emitText((string) $result);
                }
            },
            fn($caught) => $this->errorManager->resolveException(
                'app',
                'Failed to emit the application response: ' . $caught->getMessage(),
                (int) $caught->getCode(),
                $caught
            )
        );
    }

    private function emitJson(array|JsonSerializable $payload): void
    {
        if ($this->isHttpContext() && is_array($payload) && isset($payload['status']) && $this->isInt($payload['status'])) {
            http_response_code((int) $payload['status']);
        }

        $this->applyDefaultSecurityHeaders();
        $this->sendHeaderIfMissing('Content-Type', 'application/json; charset=UTF-8');

        $encoded = $this->toJson(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );

        echo $encoded;
    }

    private function emitText(string $content): void
    {
        $this->applyDefaultSecurityHeaders();
        $this->sendHeaderIfMissing('Content-Type', 'text/html; charset=UTF-8');
        echo $this->decorateHtmlIfNeeded($content);
    }

    private function emitMaintenanceResponse(): void
    {
        if ($this->isHttpContext()) {
            http_response_code(503);
            $this->applyDefaultSecurityHeaders();
            $this->sendHeaderIfMissing('Retry-After', '3600');
            $this->sendHeaderIfMissing('Content-Type', 'text/plain; charset=UTF-8');
        }

        echo 'LangelerMVC is currently in maintenance mode.';
    }

    private function sendHeaderIfMissing(string $name, string $value): void
    {
        if (!$this->isHttpContext() || headers_sent()) {
            return;
        }

        $normalizedName = $this->toLower($name) . ':';

        foreach (headers_list() as $header) {
            if ($this->startsWith($this->toLower($header), $normalizedName)) {
                return;
            }
        }

        header($name . ': ' . $value);
    }

    private function resolveRequestUri(): string
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        return $this->isString($requestUri) && $requestUri !== '' ? $requestUri : '/';
    }

    private function dispatchFrameworkHealthRequest(): mixed
    {
        $path = parse_url($this->resolveRequestUri(), PHP_URL_PATH);

        if (!$this->isString($path) || !in_array($path, ['/health', '/ready'], true)) {
            return null;
        }

        try {
            $health = $this->coreProvider->getCoreService('health');
        } catch (\Throwable) {
            return null;
        }

        if (!$health instanceof HealthManagerInterface) {
            return null;
        }

        return $path === '/ready'
            ? $health->readiness()
            : $health->liveness();
    }

    private function resolveRequestMethod(): string
    {
        $method = $this->toUpper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if ($method !== 'POST') {
            return $method;
        }

        $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? $_POST['_method'] ?? null;

        if (!$this->isString($override)) {
            return $method;
        }

        $normalizedOverride = $this->toUpper($this->trimString($override));
        $supportedOverrides = ['PUT', 'PATCH', 'DELETE', 'OPTIONS'];

        return $this->isInArray($normalizedOverride, $supportedOverrides, true)
            ? $normalizedOverride
            : $method;
    }

    private function isMaintenanceModeEnabled(): bool
    {
        return $this->normalizeBool($this->config?->get('app', 'MAINTENANCE', false), false)
            && $this->isHttpContext();
    }

    private function prepareFrameworkResponse(ResponseInterface $response): void
    {
        $this->applyDefaultSecurityHeadersToResponse($response);
        $this->decorateResponseContent($response);
    }

    private function applyDefaultSecurityHeaders(): void
    {
        foreach ($this->defaultSecurityHeaders() as $name => $value) {
            $this->sendHeaderIfMissing($name, $value);
        }
    }

    private function applyDefaultSecurityHeadersToResponse(ResponseInterface $response): void
    {
        $existing = $response->getHeaders();
        $normalized = [];

        foreach ($existing as $name => $value) {
            $normalized[$this->toLower((string) $name)] = (string) $value;
        }

        foreach ($this->defaultSecurityHeaders() as $name => $value) {
            if (isset($normalized[$this->toLower($name)])) {
                continue;
            }

            $response->addHeader($name, $value);
        }
    }

    private function decorateResponseContent(ResponseInterface $response): void
    {
        $content = $response->getContent();

        if (!$this->isString($content)) {
            return;
        }

        $response->setContent($this->decorateHtmlIfNeeded($content, $response->getHeaders()));
    }

    /**
     * @param array<string, string> $headers
     */
    private function decorateHtmlIfNeeded(string $content, array $headers = []): string
    {
        if ($this->httpSecurity === null || !$this->looksLikeHtmlResponse($content, $headers)) {
            return $content;
        }

        $session = $this->resolveSession();

        if (!$session instanceof Session) {
            return $content;
        }

        return $this->httpSecurity->decorateHtmlDocument($content, $session);
    }

    /**
     * @param array<string, string> $headers
     */
    private function looksLikeHtmlResponse(string $content, array $headers = []): bool
    {
        $contentType = '';

        foreach ($headers as $name => $value) {
            if ($this->toLower((string) $name) === 'content-type') {
                $contentType = $this->toLower((string) $value);
                break;
            }
        }

        if ($contentType !== '' && !$this->contains($contentType, 'text/html')) {
            return false;
        }

        return preg_match('/<(?:!doctype|html|head|body|main|section|form)\b/i', $content) === 1;
    }

    private function resolveSession(): ?Session
    {
        if ($this->session instanceof Session) {
            return $this->session;
        }

        try {
            $resolved = $this->coreProvider->getCoreService('session');
        } catch (\Throwable) {
            return null;
        }

        if (!$resolved instanceof Session) {
            return null;
        }

        $this->session = $resolved;

        return $this->session;
    }

    /**
     * @return array<string, string>
     */
    private function defaultSecurityHeaders(): array
    {
        return $this->httpSecurity?->defaultHeaders($this->isSecureRequest()) ?? [];
    }

    private function isSecureRequest(): bool
    {
        $https = $_SERVER['HTTPS'] ?? null;
        $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null;
        $forwardedSsl = $_SERVER['HTTP_X_FORWARDED_SSL'] ?? null;

        return ($this->isString($https) && $https !== '' && $this->toLower($https) !== 'off')
            || ($this->isString($forwardedProto) && $this->toLower($forwardedProto) === 'https')
            || ($this->isString($forwardedSsl) && $this->toLower($forwardedSsl) === 'on');
    }

    private function normalizeBool(mixed $value, bool $default = false): bool
    {
        if ($this->isBool($value)) {
            return $value;
        }

        if ($this->isString($value)) {
            $normalized = $this->var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            return $normalized ?? $default;
        }

        if ($this->isInt($value) || $this->isFloat($value)) {
            return (bool) $value;
        }

        return $default;
    }

    private function normalizeTimezone(mixed $value): string
    {
        $timezone = $this->isScalar($value) ? $this->trimString((string) $value) : 'UTC';

        return $this->isInArray($timezone, timezone_identifiers_list(), true) ? $timezone : 'UTC';
    }

    private function isHttpContext(): bool
    {
        return PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg';
    }
}
