<?php

namespace App\Core;

use Throwable;
use App\Exceptions\RouteNotFoundException;
use App\Utilities\Managers\{
    CacheManager,
    System\ErrorManager
};
use App\Utilities\Traits\ErrorTrait;
use App\Utilities\Validation\PatternValidator;

/**
 * Router Class
 *
 * Loads module route files, caches the full route state, resolves middleware,
 * and dispatches controller actions.
 */
class Router
{
    use ErrorTrait;

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
     * Named routes => normalized path.
     *
     * @var array<string, string>
     */
    private array $namedRoutes = [];

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
        private ErrorManager $errorManager
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
        $normalizedMethod = strtoupper($method);
        $normalizedPath = $this->normalizeRoutePath($this->groupPrefix . $path);

        $this->routes[$normalizedMethod][$normalizedPath] = [
            'callback' => [$controllerAlias, $action],
            'middleware' => array_values(array_merge($this->groupMiddleware, $options['middleware'] ?? [])),
            'paramRules' => $options['params'] ?? [],
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
        $this->addRoute($method, $path, $controllerAlias, $action, $options);
        $this->namedRoutes[$alias] = $this->normalizeRoutePath($this->groupPrefix . $path);
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
        $normalizedMethod = strtoupper($method);

        if (!in_array($normalizedMethod, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'], true)) {
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

        $route = $this->namedRoutes[$name];

        foreach ($params as $key => $value) {
            $route = str_replace('{' . $key . '}', (string) $value, $route);
            $route = str_replace('{' . $key . '?}', (string) $value, $route);
        }

        return (string) preg_replace('/\/?\{(\w+)\?\}/', '', $route);
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

            $this->applyMiddleware($route['middleware'] ?? []);

            return $this->moduleManager->resolveModule($route['callback'][0])
                ->{$route['callback'][1]}(...array_values($this->routeParams));
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
    private function applyMiddleware(array $middlewares): void
    {
        foreach ($middlewares as $middleware) {
            $module = $this->moduleManager->resolveModule($middleware[0] ?? '');
            $method = $middleware[1] ?? 'handle';

            if (!method_exists($module, $method)) {
                throw $this->errorManager->resolveException(
                    'middleware',
                    "Method [{$method}] not found in middleware [" . ($middleware[0] ?? '') . '].'
                );
            }

            $module->{$method}();
        }
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

        if (is_callable($definition)) {
            $definition($this);
            return;
        }

        if (is_array($definition)) {
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
        if (isset($definition['routes']) || isset($definition['namedRoutes']) || array_key_exists('fallbackRoute', $definition)) {
            $this->routes = array_replace_recursive($this->routes, $definition['routes'] ?? []);
            $this->namedRoutes = array_replace($this->namedRoutes, $definition['namedRoutes'] ?? []);
            $this->fallbackRoute = $definition['fallbackRoute'] ?? $this->fallbackRoute;
            return;
        }

        if ($this->isMethodMap($definition)) {
            $this->routes = array_replace_recursive($this->routes, $definition);
            return;
        }

        foreach ($definition as $route) {
            if (is_array($route)) {
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

        if (!is_string($controller)) {
            throw $this->errorManager->resolveException('router', 'Declarative routes require a controller.');
        }

        if (isset($route['alias']) && is_string($route['alias'])) {
            $this->addRouteWithAlias($method, $path, $controller, $action, $route['alias'], $options);
            return;
        }

        $this->addRoute($method, $path, $controller, $action, $options);
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

        foreach (array_keys($definition) as $key) {
            if (!in_array((string) $key, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'], true)) {
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

            if (!is_string($payload) || $payload === '') {
                return null;
            }

            $decoded = base64_decode($payload, true);

            if ($decoded === false) {
                return null;
            }

            $state = json_decode($decoded, true);

            return is_array($state) ? $state : null;
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
            $payload = base64_encode((string) json_encode($state, JSON_THROW_ON_ERROR));
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
            'signature' => $signature,
            'routes' => $this->routes,
            'namedRoutes' => $this->namedRoutes,
            'fallbackRoute' => $this->fallbackRoute,
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
        $this->routes = is_array($state['routes'] ?? null) ? $state['routes'] : [];
        $this->namedRoutes = is_array($state['namedRoutes'] ?? null) ? $state['namedRoutes'] : [];
        $this->fallbackRoute = is_array($state['fallbackRoute'] ?? null) ? $state['fallbackRoute'] : null;
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
        $this->groupPrefix = '';
        $this->groupMiddleware = [];
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
        return is_array($state)
            && ($state['signature'] ?? null) === $signature
            && is_array($state['routes'] ?? null);
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

        return sha1((string) json_encode($fingerprints));
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

            if (preg_match($this->convertPatternToRegex($pattern), $uri, $matches) !== 1) {
                continue;
            }

            $this->routeParams = $this->validateParams(
                array_filter($matches, fn($key) => is_string($key), ARRAY_FILTER_USE_KEY),
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
     * Allows _method override for limited clients.
     *
     * @param string $original
     * @return string
     */
    private function getHttpMethod(string $original): string
    {
        $override = $_POST['_method'] ?? $_GET['_method'] ?? null;

        if (is_string($override)) {
            $override = strtoupper($override);

            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                return $override;
            }
        }

        return strtoupper($original);
    }

    /**
     * Converts a route pattern into a regular expression.
     *
     * @param string $pattern
     * @return string
     */
    private function convertPatternToRegex(string $pattern): string
    {
        $regex = preg_replace_callback(
            '/\{(\w+)(?::([^}]+))?\}/',
            fn($matches) => isset($matches[2])
                ? '(?P<' . $matches[1] . '>' . $matches[2] . ')'
                : '(?P<' . $matches[1] . '>[^/]+)',
            $pattern
        );

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
        $path = trim($path);

        if ($path === '') {
            return '/';
        }

        $path = preg_replace('#/+#', '/', $path) ?? $path;

        if (substr($path, 0, 1) !== '/') {
            $path = '/' . $path;
        }

        if ($path !== '/' && substr($path, -1) === '/') {
            $path = rtrim($path, '/');
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

        if (!is_string($path) || $path === '') {
            return '/';
        }

        $decodedPath = rawurldecode($path);

        return $this->normalizeRoutePath($decodedPath);
    }
}
