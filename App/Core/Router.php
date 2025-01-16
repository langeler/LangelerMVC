<?php

namespace App\Core;

use App\Utilities\Handlers\{
    DataHandler,
    DataStructureHandler
};
use App\Utilities\Managers\{
    CacheManager,
    System\ErrorManager
};
use App\Utilities\Sanitation\{
    GeneralSanitizer,
    PatternSanitizer
};
use App\Utilities\Traits\{
    ArrayTrait,
    CheckerTrait,
    ErrorTrait,
    ExistenceCheckerTrait,
    ManipulationTrait,
    TypeCheckerTrait
};
use App\Utilities\Validation\{
    GeneralValidator,
    PatternValidator
};

/**
 * Router Class
 *
 * Key Features:
 *  - Group prefix + group-level middleware.
 *  - Param-based rules: e.g. 'params' => [ 'id' => ['intPos',['between'=>[1,9999]]] ].
 *  - Named routes (route('someAlias', [...])) and fallback route.
 *  - Cache-based route definitions.
 *  - Full usage of ErrorManager + ExceptionProvider aliases (no direct exception usage).
 *  - Logging via logErrorMessage(...) + wrapInTry(..., $alias).
 *  - Only the traits that are actually used: no LoopTrait or ConversionTrait.
 *  - "No temp variables" style, leveraging trait-based inlined array/string manipulations.
 */
class Router
{
    // We only use these traits, as the rest are unneeded in final usage.
    use ManipulationTrait,       // toUpper(), replace(), etc.
        ExistenceCheckerTrait,   // methodExists(), etc.
        ArrayTrait,              // reduce(), map(), filter(), walk(), mergeUnique(), etc.
        TypeCheckerTrait,        // isString(), isEmpty(), etc.
        CheckerTrait,            // contains(), etc.
        ErrorTrait;              // wrapInTry(), etc.

    /**
     * @var array<string, array<string, array>>
     * Example structure:
     * $routes['GET']['/users/{id}'] = [
     *   'callback' => ['UserController','show'],
     *   'middleware' => [...],
     *   'paramRules' => [ 'id' => ['intPos',['between'=>[1,9999]]] ]
     * ];
     */
    private array $routes = [];

    /**
     * Captured URI parameters after pattern matching.
     */
    private array $routeParams = [];

    /**
     * Named routes => path, for route(...) lookups.
     */
    private array $namedRoutes = [];

    /**
     * Fallback route if none matched, e.g. [ 'SomeControllerAlias', 'fallbackMethod' ].
     */
    private ?array $fallbackRoute = null;

    /**
     * Group-level prefix + middleware array.
     */
    private ?string $groupPrefix = null;
    private array $groupMiddleware = [];

    /**
     * Lifetime (seconds) for route definitions in cache.
     */
    private int $cacheDuration = 600;

    public function __construct(
        private DataHandler $dataHandler,
        private DataStructureHandler $dataStructureHandler,
        private CacheManager $cacheManager,
        private PatternSanitizer $patternSanitizer,
        private GeneralSanitizer $generalSanitizer,
        private PatternValidator $patternValidator,
        private GeneralValidator $generalValidator,
        private ModuleManager $moduleManager,
        private ErrorManager $errorManager
    ) {
        // Load or build routes. If any error => alias 'router'
        $this->routes = $this->wrapInTry(
            fn() => $this->cacheManager->get('routes') |> (
                $this->isString($_) && $this->isRouteCacheValid($_)
                    ? $this->dataHandler->jsonDecode($this->dataHandler->urlDecode($_))
                    : $this->cacheRoutes()
            ),
            'router'
        );
    }

    /**
     * Begins a group with prefix + optional group-level middleware.
     */
    public function group(string $prefix, array $middleware = []): void
    {
        $this->groupPrefix = $this->wrapInTry(
            fn() => $this->sanitizePath($prefix),
            'sanitization'
        );
        $this->groupMiddleware = $middleware;
    }

    /**
     * Ends the group prefix + group-level middleware context.
     */
    public function endGroup(): void
    {
        $this->groupPrefix = null;
        $this->groupMiddleware = [];
    }

    /**
     * Registers a route. $options may contain:
     *   'middleware' => [...],
     *   'params' => [ 'id'=>['intPos',['between'=>[1,999]]] ]
     */
    public function addRoute(
        string $method,
        string $path,
        string $controllerAlias,
        string $action,
        array $options = []
    ): void {
        $this->routes[$method][
            $this->wrapInTry(
                fn() => $this->sanitizePath(
                    $this->validateUri(($this->groupPrefix ?? '') . $path)
                ),
                'sanitization'
            )
        ] = [
            'callback'   => [$controllerAlias, $action],
            'middleware' => $this->merge($this->groupMiddleware, $options['middleware'] ?? []),
            'paramRules' => $options['params'] ?? []
        ];
    }

    /**
     * Registers a route plus a named alias for route(...).
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
        $this->namedRoutes[$alias] = ($this->groupPrefix ?? '') . $path;
    }

    /**
     * Shorthand to define a route by HTTP method: GET, POST, PUT, etc.
     */
    public function execute(
        string $method,
        string $path,
        string $controllerAlias,
        string $action,
        array $options = []
    ): void {
        match ($this->toUpper($method)) {
            'GET'     => $this->addRoute('GET', $path, $controllerAlias, $action, $options),
            'POST'    => $this->addRoute('POST', $path, $controllerAlias, $action, $options),
            'PUT'     => $this->addRoute('PUT', $path, $controllerAlias, $action, $options),
            'DELETE'  => $this->addRoute('DELETE', $path, $controllerAlias, $action, $options),
            'PATCH'   => $this->addRoute('PATCH', $path, $controllerAlias, $action, $options),
            'OPTIONS' => $this->addRoute('OPTIONS', $path, $controllerAlias, $action, $options),
            default   => (
                $this->errorManager->logErrorMessage(
                    "Unsupported HTTP method: {$method}",
                    __FILE__,
                    __LINE__,
                    'userError',
                    'router'
                ),
                throw $this->errorManager->resolveException(
                    'invalidArgument',
                    "Unsupported HTTP method: {$method}"
                )
            )
        };
    }

    // Shorthand route definitions:

    public function get(string $path, string $controller, string $action, array $options = []): void
    { $this->execute('GET', $path, $controller, $action, $options); }

    public function post(string $path, string $controller, string $action, array $options = []): void
    { $this->execute('POST', $path, $controller, $action, $options); }

    public function put(string $path, string $controller, string $action, array $options = []): void
    { $this->execute('PUT', $path, $controller, $action, $options); }

    public function delete(string $path, string $controller, string $action, array $options = []): void
    { $this->execute('DELETE', $path, $controller, $action, $options); }

    public function patch(string $path, string $controller, string $action, array $options = []): void
    { $this->execute('PATCH', $path, $controller, $action, $options); }

    public function options(string $path, string $controller, string $action, array $options = []): void
    { $this->execute('OPTIONS', $path, $controller, $action, $options); }

    /**
     * Creates a URL for a named route, substituting placeholders (like {id}) with $params.
     */
    public function route(string $name, array $params = []): string
    {
        return $this->namedRoutes[$name]
            ?? (
                $this->errorManager->logErrorMessage(
                    "Route name '{$name}' not found.",
                    __FILE__,
                    __LINE__,
                    'userError',
                    'router'
                ),
                throw $this->errorManager->resolveException(
                    'routeNotFound',
                    "Route name '{$name}' not found."
                )
            )
            |> $this->reduce(
                $params,
                fn($carry, $val, $key) => $this->replace("{{$key}}", $val, $carry),
                $_
            )
            |> $this->replace('/\{(\w+)\?\}/', '', $_);
    }

    /**
     * Dispatch => match route => apply middleware => call controller->action(...).
     */
    public function dispatch(string $uri, string $method): mixed
    {
        return $this->wrapInTry(
            fn() => $this->matchUriToRoute(
                $this->sanitizePath($this->validateUri($uri)),
                $this->getHttpMethod($method)
            ) |> (
                $this->applyMiddleware($_['middleware']),
                $this->moduleManager->resolveModule($_['callback'][0])
                    ->{$_['callback'][1]}(...$this->routeParams)
            ),
            'router'
        ) ?? $this->executeFallback();
    }

    /**
     * Applies route-level + group-level middleware by alias, typically ->handle().
     */
    private function applyMiddleware(array $mwConfigs): void
    {
        $this->walk(
            $mwConfigs,
            fn($mw) => $this->methodExists(
                $this->moduleManager->resolveModule($mw[0] ?? ''),
                $mw[1] ?? 'handle'
            )
                ? $this->moduleManager->resolveModule($mw[0] ?? '')
                    ->{ $mw[1] ?? 'handle' }()
                : (
                    $this->errorManager->logErrorMessage(
                        "Middleware method [" . ($mw[1] ?? 'handle') . "] not found in [" . ($mw[0] ?? '') . "].",
                        __FILE__,
                        __LINE__,
                        'userError',
                        'router'
                    ),
                    throw $this->errorManager->resolveException(
                        'middleware',
                        "Method [" . ($mw[1] ?? 'handle') . "] not found in middleware [" . ($mw[0] ?? '') . "]."
                    )
                )
        );
    }

    /**
     * Builds or loads route definitions, caches them, all under alias 'router'.
     */
    private function cacheRoutes(): array
    {
        return $this->wrapInTry(
            fn() => $this->buildRoutes() |> (
                $this->cacheManager->set(
                    'routes',
                    $this->dataHandler->urlEncode($this->dataHandler->jsonEncode($_)),
                    $this->cacheDuration
                ),
                $_
            ),
            'router'
        );
    }

    /**
     * Collects route files from all modules' 'routes' subdir, merges them into a single array.
     */
    private function buildRoutes(): array
    {
        return $this->wrapInTry(
            fn() => $this->reduce(
                $this->moduleManager->collectFiles('routes'),
                fn($acc, $file) => $this->mergeUnique($acc, require $file),
                []
            ),
            'router'
        );
    }

    /**
     * Verifies the cached routes data is valid (non-empty JSON).
     * We do not use an array validator method here, just a simple isEmpty check.
     */
    private function isRouteCacheValid(string $cached): bool
    {
        return (bool) $this->wrapInTry(
            fn() => !$this->isEmpty(
                $this->dataHandler->jsonDecode($this->dataHandler->urlDecode($cached))
            ),
            'router'
        );
    }

    /**
     * Matches a URI+method => merges placeholders => param-based rules if any.
     */
    private function matchUriToRoute(string $uri, string $method): array
    {
        return $this->keyExists($this->routes, $method)
            ? $this->reduce(
                $this->routes[$method],
                fn($found, $route, $pattern) => $found ?: (
                    preg_match($this->convertPatternToRegex($pattern), $uri, $matches)
                        ? $this->merge($route, [
                            'params' => $this->routeParams = $this->validateParams(
                                $this->filter($matches, fn($k) => $this->isString($k), ARRAY_FILTER_USE_KEY),
                                $route['paramRules'] ?? []
                            )
                        ])
                        : null
                ),
                null
            ) ?? (
                $this->errorManager->logErrorMessage(
                    "No matching route for URI: {$uri}",
                    __FILE__,
                    __LINE__,
                    'userError',
                    'router'
                ),
                throw $this->errorManager->resolveException(
                    'routeNotFound',
                    "No matching route for URI: {$uri}"
                )
            )
            : (
                $this->errorManager->logErrorMessage(
                    "No routes defined for method {$method}",
                    __FILE__,
                    __LINE__,
                    'userError',
                    'router'
                ),
                throw $this->errorManager->resolveException(
                    'routeNotFound',
                    "No routes defined for method {$method}."
                )
            );
    }

    /**
     * If no route matched, fallback or throw 'routeNotFound'.
     */
    private function executeFallback(): mixed
    {
        return $this->isNull($this->fallbackRoute)
            ? (
                $this->errorManager->logErrorMessage(
                    "No fallback route defined.",
                    __FILE__,
                    __LINE__,
                    'userError',
                    'router'
                ),
                throw $this->errorManager->resolveException(
                    'routeNotFound',
                    "No matching route and no fallback defined."
                )
            )
            : $this->moduleManager
                ->resolveModule($this->fallbackRoute[0])
                ->{$this->fallbackRoute[1]}();
    }

    /**
     * Allows _method override if client can't send PUT/PATCH/DELETE directly.
     */
    private function getHttpMethod(string $original): string
    {
        return $this->isString($_POST['_method'] ?? $_GET['_method'] ?? null)
            && $this->contains('PUT|PATCH|DELETE', $this->toUpper($_POST['_method'] ?? $_GET['_method'] ?? ''))
            ? $this->toUpper($_POST['_method'] ?? $_GET['_method'] ?? '')
            : $this->toUpper($original);
    }

    /**
     * Converts /user/{id:\d+} => named-capturing regex => e.g. (?P<id>\d+).
     */
    private function convertPatternToRegex(string $pattern): string
    {
        return '~^' . $this->replace(
            '/\{(\w+)(?::([^}]+))?\}/',
            fn($m) => $this->isSet($m[2])
                ? "(?P<{$m[1]}>{$m[2]})"
                : "(?P<{$m[1]}>[^/]+)",
            $pattern
        ) . '$~';
    }

    /**
     * Validates placeholders => param rules or default 'slug'+['notEmpty'] if none.
     */
    private function validateParams(array $placeholders, array $rules): array
    {
        return $this->map(
            fn($pName, $pVal) =>
                $this->applyParamRule($pName, $pVal, $rules[$pName] ?? ['slug',['notEmpty']]),
            $placeholders
        );
    }

    /**
     * Applies a single param rule (like ['intPos',['between'=>[1,9999]]]) via PatternValidator.
     */
    private function applyParamRule(string $paramName, mixed $value, array $ruleConfig): mixed
    {
        return $this->wrapInTry(
            fn() => $this->patternValidator->verify(
                [$paramName => $ruleConfig],
                [$paramName => $value]
            )[$paramName],
            'validation'
        );
    }

    /**
     * Sanitizes a path => 
     * 1) GeneralSanitizer => 'string' + flags ['fullSpecialChars','stripLow','stripHigh']
     * 2) PatternSanitizer => 'slug'
     */
    private function sanitizePath(string $path): string
    {
        return $this->wrapInTry(
            fn() => $this->patternSanitizer->clean(
                ['p' => ['slug']],
                $this->generalSanitizer->clean(
                    [
                        'p' => [
                            'string',
                            ['fullSpecialChars','stripLow','stripHigh']
                        ]
                    ],
                    ['p' => $this->dataHandler->urlDecode($path)]
                )
            )['p'],
            'sanitization'
        );
    }

    /**
     * Validates $uri => 'url','pathRequired' via GeneralValidator, alias 'validation'.
     */
    private function validateUri(string $uri): string
    {
        return $this->wrapInTry(
            fn() => $this->generalValidator->verify(
                [
                    'uri' => [
                        'url',
                        ['pathRequired']
                    ]
                ],
                ['uri' => $uri]
            )['uri'],
            'validation'
        );
    }
}