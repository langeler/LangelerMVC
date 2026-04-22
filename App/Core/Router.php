<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;
use App\Contracts\Http\ResponseInterface;
use App\Exceptions\RouteNotFoundException;
use App\Utilities\Managers\{
    CacheManager,
    DateTimeManager,
    System\ErrorManager
};
use App\Utilities\Handlers\DataHandler;
use App\Utilities\Managers\Security\HttpSecurityManager;
use App\Utilities\Traits\{
    ArrayTrait,
    CheckerTrait,
    ConversionTrait,
    EncodingTrait,
    ErrorTrait,
    ExistenceCheckerTrait,
    ManipulationTrait,
    TypeCheckerTrait
};
use App\Utilities\Traits\Patterns\PatternTrait;
use App\Utilities\Validation\PatternValidator;

/**
 * Router Class
 *
 * Loads module route files, caches the full route state, resolves middleware,
 * and dispatches controller actions.
 */
class Router
{
    use ErrorTrait, TypeCheckerTrait, ExistenceCheckerTrait, CheckerTrait, EncodingTrait, ConversionTrait;
    use ArrayTrait, ManipulationTrait, PatternTrait {
        ManipulationTrait::replaceText as private stringReplace;
        PatternTrait::replaceByPattern as private patternReplace;
        ManipulationTrait::trimRight as private trimRightString;
        ManipulationTrait::toLower as private toLowerString;
        ManipulationTrait::toUpper as private toUpperString;
    }

    private const CACHE_SCHEMA_VERSION = 4;

    /**
     * @var array<string, array<string, array>>
     */
    private array $routes = [];

    /**
     * Captured URI parameters after pattern matching.
     *
     * @var array<string, mixed>
     */
    private array $routeParams = [];

    /**
     * Named routes => normalized route metadata.
     *
     * @var array<string, array{path:string,method:string}>
     */
    private array $namedRoutes = [];

    /**
     * Route registration diagnostics captured during build.
     *
     * @var array{route_overrides:list<array<string, string>>, name_overrides:list<array<string, string>>}
     */
    private array $diagnostics = [
        'route_overrides' => [],
        'name_overrides' => [],
    ];

    /**
     * Fallback route callback definition.
     *
     * @var array{0: string, 1: string}|null
     */
    private ?array $fallbackRoute = null;

    /**
     * Group-level prefix + middleware.
     */
    private string $groupPrefix = '';
    private array $groupMiddleware = [];

    /**
     * Lifetime (seconds) for route definitions in cache.
     */
    private int $cacheDuration = 600;

    public function __construct(
        private CacheManager $cacheManager,
        private PatternValidator $patternValidator,
        private ModuleManager $moduleManager,
        private ErrorManager $errorManager,
        private Session $session,
        private HttpSecurityManager $httpSecurity,
        private DataHandler $dataHandler,
        private DateTimeManager $dateTimeManager
    ) {
        $this->initializeRoutes();
    }

    /**
     * Begins a route group with prefix and middleware.
     *
     * @param string $prefix
     * @param array $middleware
     * @return void
     */
    public function group(string $prefix, array $middleware = []): void
    {
        $this->groupPrefix = $this->normalizeRoutePath($prefix);
        $this->groupMiddleware = $middleware;
    }

    /**
     * Ends the current route group.
     *
     * @return void
     */
    public function endGroup(): void
    {
        $this->groupPrefix = '';
        $this->groupMiddleware = [];
    }

    /**
     * Registers a route.
     *
     * @param string $method
     * @param string $path
     * @param string $controllerAlias
     * @param string $action
     * @param array $options
     * @return void
     */
    public function addRoute(
        string $method,
        string $path,
        string $controllerAlias,
        string $action,
        array $options = []
    ): void {
        $normalizedMethod = $this->toUpperString($method);
        $normalizedPath = $this->normalizeRoutePath($this->groupPrefix . $path);
        $existing = $this->routes[$normalizedMethod][$normalizedPath] ?? null;

        if ($this->isArray($existing) && $existing !== []) {
            $this->recordRouteOverride($normalizedMethod, $normalizedPath, $existing, [$controllerAlias, $action]);
        }

        $this->routes[$normalizedMethod][$normalizedPath] = [
            'callback' => [$controllerAlias, $action],
            'middleware' => $this->getValues($this->merge($this->groupMiddleware, $options['middleware'] ?? [])),
            'paramRules' => $options['params'] ?? [],
            'csrf' => $this->resolveCsrfSetting($normalizedMethod, $options),
        ];
    }

    /**
     * Registers a named route.
     *
     * @param string $method
     * @param string $path
     * @param string $controllerAlias
     * @param string $action
     * @param string $alias
     * @param array $options
     * @return void
     */
    public function addRouteWithAlias(
        string $method,
        string $path,
        string $controllerAlias,
        string $action,
        string $alias,
        array $options = []
    ): void {
        $normalizedPath = $this->normalizeRoutePath($this->groupPrefix . $path);
        $normalizedMethod = $this->toUpperString($method);

        if (isset($this->namedRoutes[$alias])) {
            $existing = $this->namedRoutes[$alias];
            $existingPath = $this->isArray($existing) ? (string) ($existing['path'] ?? '') : (string) $existing;
            $existingMethod = $this->isArray($existing)
                ? $this->toUpperString((string) ($existing['method'] ?? 'GET'))
                : 'GET';

            if ($existingPath !== '' && ($existingPath !== $normalizedPath || $existingMethod !== $normalizedMethod)) {
                $this->recordNamedRouteOverride($alias, $existingMethod, $existingPath, $normalizedMethod, $normalizedPath);
            }
        }

        $this->addRoute($method, $path, $controllerAlias, $action, $options);
        $this->namedRoutes[$alias] = [
            'path' => $normalizedPath,
            'method' => $normalizedMethod,
        ];
    }

    /**
     * Registers a fallback route.
     *
     * @param string $controllerAlias
     * @param string $action
     * @return void
     */
    public function fallback(string $controllerAlias, string $action = 'index'): void
    {
        $this->fallbackRoute = [$controllerAlias, $action];
    }

    /**
     * Shorthand route registration.
     *
     * @param string $method
     * @param string $path
     * @param string $controllerAlias
     * @param string $action
     * @param array $options
     * @return void
     */
    public function execute(
        string $method,
        string $path,
        string $controllerAlias,
        string $action,
        array $options = []
    ): void {
        $normalizedMethod = $this->toUpperString($method);

        if (!$this->isInArray($normalizedMethod, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'], true)) {
            throw $this->errorManager->resolveException(
                'invalidArgument',
                "Unsupported HTTP method: {$method}"
            );
        }

        if (isset($options['as'])) {
            $this->addRouteWithAlias(
                $normalizedMethod,
                $path,
                $controllerAlias,
                $action,
                $options['as'],
                $options
            );
            return;
        }

        $this->addRoute($normalizedMethod, $path, $controllerAlias, $action, $options);
    }

    public function get(string $path, string $controller, string $action, array $options = []): void
    {
        $this->execute('GET', $path, $controller, $action, $options);
    }

    public function post(string $path, string $controller, string $action, array $options = []): void
    {
        $this->execute('POST', $path, $controller, $action, $options);
    }

    public function put(string $path, string $controller, string $action, array $options = []): void
    {
        $this->execute('PUT', $path, $controller, $action, $options);
    }

    public function delete(string $path, string $controller, string $action, array $options = []): void
    {
        $this->execute('DELETE', $path, $controller, $action, $options);
    }

    public function patch(string $path, string $controller, string $action, array $options = []): void
    {
        $this->execute('PATCH', $path, $controller, $action, $options);
    }

    public function options(string $path, string $controller, string $action, array $options = []): void
    {
        $this->execute('OPTIONS', $path, $controller, $action, $options);
    }

    /**
     * @return array<int, array{method:string,path:string,action:string,name:?string,middleware:list<mixed>,csrf:?bool}>
     */
    public function listRoutes(): array
    {
        $namedByRoute = [];
        $listed = [];

        foreach ($this->namedRoutes as $alias => $definition) {
            if ($this->isArray($definition)) {
                $path = (string) ($definition['path'] ?? '');
                $method = $this->toUpperString((string) ($definition['method'] ?? 'GET'));
            } else {
                $path = (string) $definition;
                $method = '';
            }

            if ($path === '') {
                continue;
            }

            $namedByRoute[$method . ' ' . $path] = $alias;
        }

        foreach ($this->routes as $method => $routes) {
            foreach ($routes as $path => $definition) {
                $callback = $definition['callback'] ?? ['', ''];

                $listed[] = [
                    'method' => (string) $method,
                    'path' => (string) $path,
                    'action' => sprintf('%s@%s', (string) ($callback[0] ?? ''), (string) ($callback[1] ?? '')),
                    'name' => $namedByRoute[$method . ' ' . $path] ?? $namedByRoute[' ' . $path] ?? null,
                    'middleware' => $this->isArray($definition['middleware'] ?? null)
                        ? $definition['middleware']
                        : [],
                    'csrf' => isset($definition['csrf']) ? (bool) $definition['csrf'] : null,
                ];
            }
        }

        usort(
            $listed,
            static fn(array $left, array $right): int => [$left['method'], $left['path']] <=> [$right['method'], $right['path']]
        );

        return $listed;
    }

    /**
     * @return array<string, mixed>
     */
    public function diagnostics(): array
    {
        return [
            'route_overrides' => $this->diagnostics['route_overrides'],
            'name_overrides' => $this->diagnostics['name_overrides'],
            'route_override_count' => count($this->diagnostics['route_overrides']),
            'name_override_count' => count($this->diagnostics['name_overrides']),
        ];
    }

    /**
     * Resolves a named route into a URL.
     *
     * @param string $name
     * @param array $params
     * @return string
     */
    public function route(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw $this->errorManager->resolveException(
                'routeNotFound',
                "Route name '{$name}' not found."
            );
        }

        $definition = $this->namedRoutes[$name];
        $route = $this->isArray($definition)
            ? (string) ($definition['path'] ?? '')
            : (string) $definition;

        foreach ($params as $key => $value) {
            $route = $this->stringReplace('{' . $key . '}', (string) $value, $route);
            $route = $this->stringReplace('{' . $key . '?}', (string) $value, $route);
        }

        return (string) $this->patternReplace('/\/?\{(\w+)\?\}/', '', $route);
    }

    /**
     * Dispatches a request URI and method.
     *
     * @param string $uri
     * @param string $method
     * @return mixed
     */
    public function dispatch(string $uri, string $method): mixed
    {
        try {
            $route = $this->matchUriToRoute(
                $this->normalizeRequestPath($uri),
                $this->getHttpMethod($method)
            );

            if (($route['csrf'] ?? false) === true && !$this->requestHasValidCsrfToken()) {
                return $this->buildCsrfViolationResponse($uri);
            }

            $middlewareResponse = $this->applyMiddleware($route['middleware'] ?? []);

            if ($middlewareResponse instanceof ResponseInterface) {
                return $middlewareResponse;
            }

            return $this->moduleManager->resolveModule($route['callback'][0])
                ->{$route['callback'][1]}(...$this->getValues($this->routeParams));
        } catch (RouteNotFoundException) {
            return $this->executeFallback();
        } catch (Throwable $exception) {
            throw $this->errorManager->resolveException(
                'router',
                $exception->getMessage(),
                $exception->getCode(),
                $exception
            );
        }
    }

    /**
     * Initializes routes from cache or route files.
     *
     * @return void
     */
    private function initializeRoutes(): void
    {
        $routeFiles = $this->moduleManager->collectFiles('Routes');
        $signature = $this->buildRouteSignature($routeFiles);
        $cachedState = $this->loadCachedRouteState();

        if ($this->isRouteCacheValid($cachedState, $signature)) {
            $this->hydrateRouteState($cachedState);
            return;
        }

        $this->rebuildRoutes($routeFiles, $signature);
    }

    /**
     * Applies middleware callbacks by alias.
     *
     * @param array $middlewares
     * @return void
     */
    private function applyMiddleware(array $middlewares): ?ResponseInterface
    {
        foreach ($middlewares as $middleware) {
            $module = $this->moduleManager->resolveModule($middleware[0] ?? '');
            $method = $middleware[1] ?? 'handle';

            if (!$this->methodExists($module, $method)) {
                throw $this->errorManager->resolveException(
                    'middleware',
                    "Method [{$method}] not found in middleware [" . ($middleware[0] ?? '') . '].'
                );
            }

            $result = $module->{$method}();

            if ($result instanceof ResponseInterface) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Rebuilds routes from route files and refreshes cache.
     *
     * @param array $routeFiles
     * @param string $signature
     * @return void
     */
    private function rebuildRoutes(array $routeFiles, string $signature): void
    {
        $this->resetRouteState();

        foreach ($routeFiles as $file) {
            $this->loadRouteFile($file);
        }

        $this->persistRouteState($this->exportRouteState($signature));
    }

    /**
     * Loads an individual route file.
     *
     * @param string $file
     * @return void
     */
    private function loadRouteFile(string $file): void
    {
        $definition = require $file;

        if ($this->isCallable($definition)) {
            $definition($this);
            return;
        }

        if ($this->isArray($definition)) {
            $this->importRouteArray($definition);
            return;
        }

        throw $this->errorManager->resolveException(
            'router',
            "Route file '{$file}' must return an array or callable."
        );
    }

    /**
     * Imports route definitions from an array.
     *
     * @param array $definition
     * @return void
     */
    private function importRouteArray(array $definition): void
    {
        if (isset($definition['routes']) || isset($definition['namedRoutes']) || $this->keyExists($definition, 'fallbackRoute')) {
            $this->routes = $this->replaceRecursive($this->routes, $definition['routes'] ?? []);
            $this->namedRoutes = $this->normalizeNamedRouteState(
                $this->replaceElements($this->namedRoutes, $definition['namedRoutes'] ?? [])
            );
            $this->fallbackRoute = $definition['fallbackRoute'] ?? $this->fallbackRoute;
            return;
        }

        if ($this->isMethodMap($definition)) {
            $this->routes = $this->replaceRecursive($this->routes, $definition);
            return;
        }

        foreach ($definition as $route) {
            if ($this->isArray($route)) {
                $this->registerDeclarativeRoute($route);
            }
        }
    }

    /**
     * Registers a declarative route array.
     *
     * @param array $route
     * @return void
     */
    private function registerDeclarativeRoute(array $route): void
    {
        if (isset($route['fallback']) && $route['fallback'] === true) {
            $this->fallback($route['controller'], $route['action'] ?? 'index');
            return;
        }

        $method = $route['method'] ?? 'GET';
        $path = $route['path'] ?? '/';
        $controller = $route['controller'] ?? null;
        $action = $route['action'] ?? 'index';
        $options = $route['options'] ?? [];

        if (!$this->isString($controller)) {
            throw $this->errorManager->resolveException('router', 'Declarative routes require a controller.');
        }

        if (isset($route['alias']) && $this->isString($route['alias'])) {
            $this->addRouteWithAlias($method, $path, $controller, $action, $route['alias'], $options);
            return;
        }

        $this->addRoute($method, $path, $controller, $action, $options);
    }

    private function resolveCsrfSetting(string $method, array $options): bool
    {
        if (array_key_exists('csrf', $options)) {
            return (bool) $options['csrf'];
        }

        return $this->httpSecurity->requiresCsrfProtection($method);
    }

    /**
     * Determines whether an array matches the compiled route map structure.
     *
     * @param array $definition
     * @return bool
     */
    private function isMethodMap(array $definition): bool
    {
        if ($definition === []) {
            return false;
        }

        foreach ($this->getKeys($definition) as $key) {
            if (!$this->isInArray((string) $key, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Loads cached route state.
     *
     * @return array|null
     */
    private function loadCachedRouteState(): ?array
    {
        try {
            $payload = $this->cacheManager->get('routes');

            if (!$this->isString($payload) || $payload === '') {
                return null;
            }

            $decoded = $this->base64DecodeString($payload, true);

            if ($decoded === false) {
                return null;
            }

            $state = $this->fromJson($decoded, true);

            return $this->isArray($state) ? $state : null;
        } catch (Throwable $exception) {
            $this->errorManager->logThrowable($exception, 'router', 'userWarning');
            return null;
        }
    }

    /**
     * Persists route state to cache.
     *
     * @param array $state
     * @return void
     */
    private function persistRouteState(array $state): void
    {
        try {
            $payload = $this->base64EncodeString($this->toJson($state, JSON_THROW_ON_ERROR));
            $this->cacheManager->set('routes', $payload, $this->cacheDuration);
        } catch (Throwable $exception) {
            $this->errorManager->logThrowable($exception, 'router', 'userWarning');
        }
    }

    /**
     * Exports the full route state for caching.
     *
     * @param string $signature
     * @return array
     */
    private function exportRouteState(string $signature): array
    {
        return [
            'version' => self::CACHE_SCHEMA_VERSION,
            'signature' => $signature,
            'routes' => $this->routes,
            'namedRoutes' => $this->namedRoutes,
            'fallbackRoute' => $this->fallbackRoute,
            'diagnostics' => $this->diagnostics,
        ];
    }

    /**
     * Restores route state from a cached payload.
     *
     * @param array $state
     * @return void
     */
    private function hydrateRouteState(array $state): void
    {
        $this->routes = $this->isArray($state['routes'] ?? null) ? $state['routes'] : [];
        $this->namedRoutes = $this->normalizeNamedRouteState(
            $this->isArray($state['namedRoutes'] ?? null) ? $state['namedRoutes'] : []
        );
        $this->fallbackRoute = $this->isArray($state['fallbackRoute'] ?? null) ? $state['fallbackRoute'] : null;
        $this->diagnostics = $this->normalizeDiagnostics(
            $this->isArray($state['diagnostics'] ?? null) ? $state['diagnostics'] : []
        );
    }

    /**
     * Clears in-memory route state before a rebuild.
     *
     * @return void
     */
    private function resetRouteState(): void
    {
        $this->routes = [];
        $this->routeParams = [];
        $this->namedRoutes = [];
        $this->fallbackRoute = null;
        $this->diagnostics = [
            'route_overrides' => [],
            'name_overrides' => [],
        ];
        $this->groupPrefix = '';
        $this->groupMiddleware = [];
    }

    /**
     * @param array<string, mixed> $state
     * @return array<string, array{path:string,method:string}>
     */
    private function normalizeNamedRouteState(array $state): array
    {
        $normalized = [];

        foreach ($state as $alias => $definition) {
            if ($this->isArray($definition)) {
                $path = (string) ($definition['path'] ?? '');
                $method = $this->toUpperString((string) ($definition['method'] ?? 'GET'));
            } else {
                $path = (string) $definition;
                $method = 'GET';
            }

            if ($path === '') {
                continue;
            }

            $normalized[(string) $alias] = [
                'path' => $path,
                'method' => $method,
            ];
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $existing
     * @param array{0:string,1:string} $incoming
     */
    private function recordRouteOverride(string $method, string $path, array $existing, array $incoming): void
    {
        $this->diagnostics['route_overrides'][] = [
            'method' => $method,
            'path' => $path,
            'previous' => sprintf('%s@%s', (string) ($existing['callback'][0] ?? ''), (string) ($existing['callback'][1] ?? '')),
            'incoming' => sprintf('%s@%s', $incoming[0], $incoming[1]),
        ];
    }

    private function recordNamedRouteOverride(string $alias, string $existingMethod, string $existingPath, string $incomingMethod, string $incomingPath): void
    {
        $this->diagnostics['name_overrides'][] = [
            'alias' => $alias,
            'previous' => trim($existingMethod . ' ' . $existingPath),
            'incoming' => trim($incomingMethod . ' ' . $incomingPath),
        ];
    }

    /**
     * @param array<string, mixed> $state
     * @return array{route_overrides:list<array<string, string>>, name_overrides:list<array<string, string>>}
     */
    private function normalizeDiagnostics(array $state): array
    {
        $normalized = [
            'route_overrides' => [],
            'name_overrides' => [],
        ];

        foreach (['route_overrides', 'name_overrides'] as $key) {
            $entries = $state[$key] ?? [];

            if (!$this->isArray($entries)) {
                continue;
            }

            foreach ($entries as $entry) {
                if (!$this->isArray($entry)) {
                    continue;
                }

                $normalized[$key][] = array_map('strval', $entry);
            }
        }

        return $normalized;
    }

    /**
     * Validates cached route state against the current route signature.
     *
     * @param array|null $state
     * @param string $signature
     * @return bool
     */
    private function isRouteCacheValid(?array $state, string $signature): bool
    {
        return $this->isArray($state)
            && (int) ($state['version'] ?? 1) === self::CACHE_SCHEMA_VERSION
            && ($state['signature'] ?? null) === $signature
            && $this->isArray($state['routes'] ?? null);
    }

    /**
     * Builds a stable signature for route file contents.
     *
     * @param array $routeFiles
     * @return string
     */
    private function buildRouteSignature(array $routeFiles): string
    {
        $fingerprints = [];

        foreach ($routeFiles as $file) {
            $fingerprints[] = [
                'file' => $file,
                'mtime' => @filemtime($file) ?: 0,
                'size' => @filesize($file) ?: 0,
            ];
        }

        usort($fingerprints, fn($left, $right) => strcmp($left['file'], $right['file']));

        return sha1($this->toJson($fingerprints, JSON_THROW_ON_ERROR));
    }

    /**
     * Matches a normalized URI and method to a route definition.
     *
     * @param string $uri
     * @param string $method
     * @return array
     */
    private function matchUriToRoute(string $uri, string $method): array
    {
        if (!isset($this->routes[$method])) {
            throw $this->errorManager->resolveException(
                'routeNotFound',
                "No routes defined for method {$method}."
            );
        }

        foreach ($this->routes[$method] as $pattern => $route) {
            $matches = [];

            if ($this->match($this->convertPatternToRegex($pattern), $uri, $matches) !== 1) {
                continue;
            }

            $this->routeParams = $this->validateParams(
                $this->filter($matches, fn($key) => $this->isString($key), ARRAY_FILTER_USE_KEY),
                $route['paramRules'] ?? []
            );

            $route['params'] = $this->routeParams;

            return $route;
        }

        throw $this->errorManager->resolveException(
            'routeNotFound',
            "No matching route for URI: {$uri}"
        );
    }

    /**
     * Executes the fallback route when defined.
     *
     * @return mixed
     */
    private function executeFallback(): mixed
    {
        if ($this->fallbackRoute === null) {
            throw $this->errorManager->resolveException(
                'routeNotFound',
                'No matching route and no fallback defined.'
            );
        }

        return $this->moduleManager
            ->resolveModule($this->fallbackRoute[0])
            ->{$this->fallbackRoute[1]}();
    }

    /**
     * Normalize the already-resolved HTTP method for routing.
     *
     * The application runtime owns method-override handling so the router can
     * focus purely on dispatch semantics.
     */
    private function getHttpMethod(string $original): string
    {
        return $this->toUpperString($original);
    }

    private function requestHasValidCsrfToken(): bool
    {
        return $this->httpSecurity->hasValidCsrfToken(
            $this->session,
            $this->requestPayload(),
            $this->requestHeaders()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function requestPayload(): array
    {
        return $this->isArray($_POST ?? null) ? $_POST : [];
    }

    /**
     * @return array<string, mixed>
     */
    private function requestHeaders(): array
    {
        if ($this->functionExists('getallheaders')) {
            $headers = getallheaders();

            if ($this->isArray($headers)) {
                return $headers;
            }
        }

        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (!$this->isString($key) || !$this->isScalar($value)) {
                continue;
            }

            if ($this->startsWith($key, 'HTTP_')) {
                $headers[$this->replaceText('_', '-', $this->substring($key, 5))] = (string) $value;
            }
        }

        return $headers;
    }

    private function buildCsrfViolationResponse(string $uri): ResponseInterface
    {
        $response = new FrameworkResponse($this->dataHandler, $this->dateTimeManager);
        $message = 'CSRF token mismatch.';

        if ($this->requestExpectsJson($uri)) {
            return $response->asJson([
                'status' => 419,
                'error' => 'csrf_token_mismatch',
                'message' => $message,
            ], 419, ['Cache-Control' => 'no-store']);
        }

        return $response->asHtml(
            '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>CSRF token mismatch</title></head><body><main><h1>Security Check Failed</h1><p>'
            . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
            . '</p><p>Please refresh the page and try again.</p></main></body></html>',
            419,
            ['Cache-Control' => 'no-store']
        );
    }

    private function requestExpectsJson(string $uri): bool
    {
        $accept = $this->toLowerString((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        $requestedWith = $this->toLowerString((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        $path = $this->normalizeRequestPath($uri);

        return $this->startsWith($path, '/api/')
            || $this->contains($accept, 'application/json')
            || $this->contains($accept, 'application/vnd.api+json')
            || $requestedWith === 'xmlhttprequest';
    }

    /**
     * Converts a route pattern into a regular expression.
     *
     * @param string $pattern
     * @return string
     */
    private function convertPatternToRegex(string $pattern): string
    {
        $regex = $this->replaceCallback(
            '/\{(\w+)(?::([^}]+))?\}/',
            fn($matches) => isset($matches[2])
                ? '(?P<' . $matches[1] . '>' . $matches[2] . ')'
                : '(?P<' . $matches[1] . '>[^/]+)',
            $pattern
        ) ?? $pattern;

        return '#^' . $regex . '$#';
    }

    /**
     * Validates captured route parameters when rules are defined.
     *
     * @param array $placeholders
     * @param array $rules
     * @return array
     */
    private function validateParams(array $placeholders, array $rules): array
    {
        $validated = [];

        foreach ($placeholders as $name => $value) {
            if (!isset($rules[$name])) {
                $validated[$name] = $value;
                continue;
            }

            $validated[$name] = $this->patternValidator->verify(
                [$name => $rules[$name]],
                [$name => $value]
            )[$name];
        }

        return $validated;
    }

    /**
     * Normalizes a route pattern while preserving placeholders.
     *
     * @param string $path
     * @return string
     */
    private function normalizeRoutePath(string $path): string
    {
        $path = $this->trimString($path);

        if ($path === '') {
            return '/';
        }

        $path = $this->patternReplace('#/+#', '/', $path) ?? $path;

        if (!$this->startsWith($path, '/')) {
            $path = '/' . $path;
        }

        if ($path !== '/' && $this->endsWith($path, '/')) {
            $path = $this->trimRightString($path, '/');
        }

        return $path === '' ? '/' : $path;
    }

    /**
     * Normalizes a request URI into a clean path.
     *
     * @param string $uri
     * @return string
     */
    private function normalizeRequestPath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH);

        if (!$this->isString($path) || $path === '') {
            return '/';
        }

        $decodedPath = $this->decodeStringFromRawUrl($path);

        return $this->normalizeRoutePath($decodedPath);
    }
}
