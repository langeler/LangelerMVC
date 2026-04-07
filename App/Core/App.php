<?php

declare(strict_types=1);

namespace App\Core;

use App\Contracts\Http\ResponseInterface;
use App\Providers\CoreProvider;
use App\Utilities\Managers\System\ErrorManager;
use App\Utilities\Traits\ErrorTrait;
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
    use ErrorTrait;

    private bool $booted = false;
    private bool $maintenanceMode = false;
    private ?Config $config = null;
    private ?Router $router = null;

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
                    $result->send();
                    return;
                }

                if (is_object($result) && method_exists($result, 'send')) {
                    $result->send();
                    return;
                }

                if ($result === null) {
                    return;
                }

                if (is_array($result) || $result instanceof JsonSerializable) {
                    $this->emitJson($result);
                    return;
                }

                if (is_scalar($result) || $result instanceof Stringable) {
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
        $this->sendHeaderIfMissing('Content-Type', 'application/json; charset=UTF-8');

        $encoded = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );

        echo $encoded;
    }

    private function emitText(string $content): void
    {
        $this->sendHeaderIfMissing('Content-Type', 'text/html; charset=UTF-8');

        echo $content;
    }

    private function emitMaintenanceResponse(): void
    {
        if ($this->isHttpContext()) {
            http_response_code(503);
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

        $normalizedName = strtolower($name) . ':';

        foreach (headers_list() as $header) {
            if (str_starts_with(strtolower($header), $normalizedName)) {
                return;
            }
        }

        header($name . ': ' . $value);
    }

    private function resolveRequestUri(): string
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';

        return is_string($requestUri) && $requestUri !== '' ? $requestUri : '/';
    }

    private function resolveRequestMethod(): string
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if ($method !== 'POST') {
            return $method;
        }

        $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? $_POST['_method'] ?? null;

        if (!is_string($override)) {
            return $method;
        }

        $normalizedOverride = strtoupper(trim($override));
        $supportedOverrides = ['PUT', 'PATCH', 'DELETE', 'OPTIONS'];

        return in_array($normalizedOverride, $supportedOverrides, true)
            ? $normalizedOverride
            : $method;
    }

    private function isMaintenanceModeEnabled(): bool
    {
        return $this->normalizeBool($this->config?->get('app', 'MAINTENANCE', false), false)
            && $this->isHttpContext();
    }

    private function normalizeBool(mixed $value, bool $default = false): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            return $normalized ?? $default;
        }

        if (is_int($value) || is_float($value)) {
            return (bool) $value;
        }

        return $default;
    }

    private function normalizeTimezone(mixed $value): string
    {
        $timezone = is_scalar($value) ? trim((string) $value) : 'UTC';

        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : 'UTC';
    }

    private function isHttpContext(): bool
    {
        return PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg';
    }
}
