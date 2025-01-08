<?php

namespace App\Core;

use App\Exceptions\Http\MiddlewareException;
use App\Exceptions\RouteNotFoundException;
use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Finders\FileFinder;
use App\Utilities\Handlers\DataHandler;
use App\Utilities\Handlers\DataStructureHandler;
use App\Utilities\Managers\CacheManager;
use App\Utilities\Managers\ReflectionManager;
use App\Utilities\Sanitation\GeneralSanitizer;
use App\Utilities\Sanitation\PatternSanitizer;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\CheckerTrait;
use App\Utilities\Traits\ConversionTrait;
use App\Utilities\Traits\ExistenceCheckerTrait;
use App\Utilities\Traits\LoopTrait;
use App\Utilities\Traits\ManipulationTrait;
use App\Utilities\Traits\TypeCheckerTrait;
use App\Utilities\Validation\GeneralValidator;
use App\Utilities\Validation\PatternValidator;
use Throwable;

/**
 * The Router class provides a robust framework for handling HTTP routing.
 * It supports route registration, organization, and dispatching while offering features
 * such as middleware processing, route groups, named routes, caching, and module-based route configurations.
 */
class Router
{
	// Traits for shared utility methods
	/**
	 * Provides shared utility methods for tasks such as data manipulation,
	 * validation, existence checking, and type verification.
	 */
	 use ManipulationTrait,
			ExistenceCheckerTrait,
			ArrayTrait,
			TypeCheckerTrait,
			CheckerTrait,
			LoopTrait,
			ConversionTrait;

		// Properties to hold route configurations and state
		/**
		 * @var array $routes Holds the registered routes categorized by HTTP methods.
		 * @var array $routeParams Stores parameters extracted from matched routes.
		 * @var array $namedRoutes Holds routes with aliases for dynamic URL generation.
		 * @var ?array $fallbackRoute Defines the fallback route configuration.
		 * @var ?string $groupPrefix Defines the prefix applied to all routes in the current group.
		 * @var array $groupMiddleware Holds middleware applied to routes in the current group.
		 * @var int $cacheDuration Specifies the duration (in seconds) for route cache validity.
		 * @var string $modulesPath Stores the path to the Modules directory.
		 * @var array $modulePaths Stores the paths to individual modules.
		 */
		private array $routes = [];
		private array $routeParams = [];
		private array $namedRoutes = [];
		private ?array $fallbackRoute = null;
		private ?string $groupPrefix = null;
		private array $groupMiddleware = [];
		private int $cacheDuration = 600;
		private string $modulesPath;
		private array $modulePaths = [];

		/**
		 * Constructor for the Router class.
		 *
		 * Initializes the Router by setting up modules and loading cached routes.
		 * Injects dependencies for directory and file management, data handling,
		 * validation, sanitation, caching, and reflection-based dynamic execution.
		 *
		 * @param DirectoryFinder $directoryFinder Service to locate directories.
		 * @param FileFinder $fileFinder Service to locate files.
		 * @param DataHandler $dataHandler Service for data manipulation.
		 * @param DataStructureHandler $dataStructureHandler Service for data structure operations.
		 * @param CacheManager $cacheManager Service for caching routes.
		 * @param ReflectionManager $reflector Service for reflection and dynamic execution.
		 * @param PatternSanitizer $patternSanitizer Service for sanitizing patterns.
		 * @param GeneralSanitizer $generalSanitizer Service for general data sanitation.
		 * @param PatternValidator $patternValidator Service for validating patterns.
		 * @param GeneralValidator $generalValidator Service for general validation tasks.
		 */
		public function __construct(
			private DirectoryFinder $directoryFinder,
			private FileFinder $fileFinder,
			private DataHandler $dataHandler,
			private DataStructureHandler $dataStructureHandler,
			private CacheManager $cacheManager,
			private ReflectionManager $reflector,
			private PatternSanitizer $patternSanitizer,
			private GeneralSanitizer $generalSanitizer,
			private PatternValidator $patternValidator,
			private GeneralValidator $generalValidator
		) {
			// Initialize modules and load cached routes during instantiation
			$this->wrapInTry(fn() => $this->setupModules());
			$this->wrapInTry(fn() => $this->loadRouteCache());
		}
	}

	/**
	 * Initializes the modules by locating the Modules directory and scanning its contents.
	 * Ensures the Modules directory exists and that valid module paths are available.
	 *
	 * @throws MiddlewareException If the Modules directory is not found or is empty.
	 */
	private function setupModules(): void
	{
		$this->modulesPath = $this->directoryFinder->find(['name' => 'Modules'])[0]
			?? throw new MiddlewareException("Modules directory not found.");

		$this->modulePaths = $this->directoryFinder->scan($this->modulesPath)
			?: throw new MiddlewareException("No modules found in the Modules directory.");
	}

	/**
	 * Registers a group of routes with a shared prefix and middleware.
	 * The prefix is prepended to all route paths within the group,
	 * and the middleware is applied to each route in the group.
	 *
	 * @param string $prefix The shared prefix for the route group.
	 * @param array $middleware Middleware to apply to all routes in the group.
	 */
	public function group(string $prefix, array $middleware = []): void
	{
		$this->groupPrefix = $this->sanitizePath($prefix);
		$this->groupMiddleware = $middleware;
	}

	/**
	 * Ends the current route group configuration.
	 * Resets the group prefix and middleware, ensuring subsequent routes
	 * are not affected by the previous group settings.
	 */
	public function endGroup(): void
	{
		$this->groupPrefix = null;
		$this->groupMiddleware = [];
	}

	/**
	 * Adds a route to the routing table for a specific HTTP method.
	 * Validates and sanitizes the route path, links the route to a controller-action callback,
	 * and associates middleware.
	 *
	 * @param string $method The HTTP method (e.g., GET, POST).
	 * @param string $path The route path.
	 * @param string $controller The controller class handling the route.
	 * @param string $action The method in the controller to invoke.
	 * @param array $middleware Middleware to apply to the route.
	 * @throws MiddlewareException If the controller or action is invalid.
	 */
	public function addRoute(string $method, string $path, string $controller, string $action, array $middleware = []): void
	{
		$this->routes[$method][$this->sanitizePath($this->validateUri(($this->groupPrefix ?? '') . $path))] = [
			'callback' => [$this->getControllerPath($controller), $action],
			'middleware' => $this->map(
				fn($middlewareConfig) => [$this->getMiddlewarePath($middlewareConfig[0]), $middlewareConfig[1] ?? 'handle'],
				$middleware
			),
		];

		$this->validateController($controller, $action);
	}

	/**
	 * Adds a route with a specified alias for named route generation.
	 * Registers the alias in the namedRoutes array for dynamic URL creation.
	 *
	 * @param string $method The HTTP method (e.g., GET, POST).
	 * @param string $path The route path.
	 * @param string $controller The controller class handling the route.
	 * @param string $action The method in the controller to invoke.
	 * @param string $alias The alias for the named route.
	 * @param array $middleware Middleware to apply to the route.
	 * @throws MiddlewareException If the controller or action is invalid.
	 */
	public function addRouteWithAlias(string $method, string $path, string $controller, string $action, string $alias, array $middleware = []): void
	{
		$this->addRoute($method, $path, $controller, $action, $middleware);
		$this->namedRoutes[$alias] = $path;
	}

	/**
	 * Prioritizes routes for a specified HTTP method.
	 * Ensures more specific patterns are matched first by converting routes into a sortable structure.
	 *
	 * @param string $method The HTTP method for which to prioritize routes.
	 * @return array The prioritized routes with patterns and their configurations.
	 */
	private function sortRoutes(string $method): array
	{
		return $this->map(
			fn($pattern, $route) => ['pattern' => $pattern, 'route' => $route],
			$this->routes[$method] ?? []
		);
	}

	/**
	 * Executes the addition of a route for the specified HTTP method.
	 * Maps the HTTP method to its corresponding route configuration using a match expression.
	 *
	 * @param string $method The HTTP method (e.g., GET, POST, PUT).
	 * @param string $path The route path.
	 * @param string $controller The controller class handling the route.
	 * @param string $action The method in the controller to invoke.
	 * @param array $middleware Middleware to apply to the route.
	 * @throws \InvalidArgumentException If an unsupported HTTP method is provided.
	 */
	public function execute(string $method, string $path, string $controller, string $action, array $middleware = []): void
	{
		return match ($this->toUpper($method)) {
			'GET' => $this->addRoute('GET', $path, $controller, $action, $middleware),
			'POST' => $this->addRoute('POST', $path, $controller, $action, $middleware),
			'PUT' => $this->addRoute('PUT', $path, $controller, $action, $middleware),
			'DELETE' => $this->addRoute('DELETE', $path, $controller, $action, $middleware),
			'PATCH' => $this->addRoute('PATCH', $path, $controller, $action, $middleware),
			'OPTIONS' => $this->addRoute('OPTIONS', $path, $controller, $action, $middleware),
			default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
		};
	}

	/**
	 * Defines a GET route.
	 *
	 * @param string $path The route path.
	 * @param string $controller The controller class to handle the route.
	 * @param string $action The method in the controller to invoke.
	 * @param array $middleware Middleware to apply to the route.
	 */
	public function get(string $path, string $controller, string $action, array $middleware = []): void
	{
		$this->execute('GET', $path, $controller, $action, $middleware);
	}

	/**
	 * Defines a POST route.
	 *
	 * @param string $path The route path.
	 * @param string $controller The controller class to handle the route.
	 * @param string $action The method in the controller to invoke.
	 * @param array $middleware Middleware to apply to the route.
	 */
	public function post(string $path, string $controller, string $action, array $middleware = []): void
	{
		$this->execute('POST', $path, $controller, $action, $middleware);
	}

	/**
	 * Defines a PUT route.
	 *
	 * @param string $path The route path.
	 * @param string $controller The controller class to handle the route.
	 * @param string $action The method in the controller to invoke.
	 * @param array $middleware Middleware to apply to the route.
	 */
	public function put(string $path, string $controller, string $action, array $middleware = []): void
	{
		$this->execute('PUT', $path, $controller, $action, $middleware);
	}

	/**
	 * Defines a DELETE route.
	 *
	 * @param string $path The route path.
	 * @param string $controller The controller class to handle the route.
	 * @param string $action The method in the controller to invoke.
	 * @param array $middleware Middleware to apply to the route.
	 */
	public function delete(string $path, string $controller, string $action, array $middleware = []): void
	{
		$this->execute('DELETE', $path, $controller, $action, $middleware);
	}

	/**
	 * Defines a PATCH route.
	 *
	 * @param string $path The route path.
	 * @param string $controller The controller class to handle the route.
	 * @param string $action The method in the controller to invoke.
	 * @param array $middleware Middleware to apply to the route.
	 */
	public function patch(string $path, string $controller, string $action, array $middleware = []): void
	{
		$this->execute('PATCH', $path, $controller, $action, $middleware);
	}

	/**
	 * Defines an OPTIONS route.
	 *
	 * @param string $path The route path.
	 * @param string $controller The controller class to handle the route.
	 * @param string $action The method in the controller to invoke.
	 * @param array $middleware Middleware to apply to the route.
	 */
	public function options(string $path, string $controller, string $action, array $middleware = []): void
	{
		$this->execute('OPTIONS', $path, $controller, $action, $middleware);
	}

	/**
	 * Retrieves a route by its name and dynamically generates a URL.
	 * Replaces route parameters with the provided values and removes optional
	 * parameters if they are not supplied.
	 *
	 * @param string $name The name of the route.
	 * @param array $params Key-value pairs of parameters to replace in the route.
	 * @return string The dynamically generated URL.
	 * @throws RouteNotFoundException If the named route does not exist.
	 */
	public function route(string $name, array $params = []): string
	{
		return $this->reduce(
			$params,
			fn($path, $value, $key) => $this->replace("{{$key}}", $value, $path),
			$this->namedRoutes[$name]
				?? throw new RouteNotFoundException("Route name '{$name}' not found.")
		) |> $this->replace('/\{(\w+)\?\}/', '', $path); // Remove optional parameters
	}

	/**
	 * Loads the routes either from the cache or by building them from the module files.
	 * If cached routes are invalid or unavailable, it rebuilds and caches them.
	 */
	private function loadRouteCache(): void
	{
		$this->routes = $this->wrapInTry(function () {
			$cachedRoutes = $this->cacheManager->get('routes');
			return $this->isString($cachedRoutes) && $this->isRouteCacheValid($cachedRoutes)
				? $this->dataHandler->jsonDecode($this->dataHandler->urlDecode($cachedRoutes))
				: $this->cacheRoutes();
		});
	}

	/**
	 * Validates whether the cached routes are valid and non-empty.
	 *
	 * @param string $cachedRoutes The cached routes in string format.
	 * @return bool True if the cached routes are valid, otherwise false.
	 */
	private function isRouteCacheValid(string $cachedRoutes): bool
	{
		return $this->wrapInTry(fn() =>
			$this->generalValidator->verify(
				['routes' => ['array', ['notEmpty']]],
				['routes' => $this->dataHandler->jsonDecode($this->dataHandler->urlDecode($cachedRoutes))]
			)['routes']
		) ?? false;
	}

	/**
	 * Caches the routes after building them from the modules and returns the routes.
	 *
	 * @return array The built and cached routes.
	 */
	private function cacheRoutes(): array
	{
		return $this->wrapInTry(fn() => $this->cacheManager->set(
			'routes',
			$this->dataHandler->urlEncode(
				$this->dataHandler->jsonEncode($this->buildRoutes())
			),
			$this->cacheDuration
		) ?: $this->buildRoutes());
	}

	/**
	 * Builds the routes from all available modules.
	 *
	 * @return array An array of all routes built from the modules.
	 */
	private function compileRoutes(): array
	{
		return $this->reduce(
			$this->modulePaths,
			fn($acc, $modulePath) => $this->mergeUnique($acc, $this->processModuleRoutes($modulePath)),
			[]
		);
	}

	/**
	 * Processes and returns routes from a specific module path.
	 *
	 * @param string $modulePath The path to the module.
	 * @return array The processed routes for the given module.
	 * @throws MiddlewareException If the routes directory is not found in the module.
	 */
	private function processModuleRoutes(string $modulePath): array
	{
		return $this->reduce(
			$this->fileFinder->find(['extension' => 'php'],
				$this->directoryFinder->find(['name' => 'Routes'], $modulePath)[0]
					?? throw new MiddlewareException("Routes directory not found in module.")
			),
			fn($acc, $file) => $this->mergeUnique($acc, require $file),
			[]
		);
	}

	/**
	 * Dispatches the given URI and HTTP method to the appropriate route.
	 * Handles middleware execution, parameter extraction, and invokes the specified controller action.
	 * Ensures standardized error handling through the wrapInTry method.
	 *
	 * @param string $uri The request URI.
	 * @param string $method The HTTP method (e.g., GET, POST).
	 * @return mixed The result of the invoked controller action.
	 * @throws MiddlewareException If middleware execution or method invocation fails.
	 * @throws RouteNotFoundException If no matching route is found and no fallback is defined.
	 */
	public function dispatch(string $uri, string $method): mixed
	{
		return $this->wrapInTry(
			fn() => $this->reflector->invokeMethodWithArgs(
				$this->reflector->getMethodInfo(
					$route = $this->matchUriToRoute(
						$this->sanitizePath($this->validateUri($uri)),
						$this->getHttpMethod($method)
					)['callback'],
					$route['callback'][1]
				),
				$this->reflector->instantiateClass($route['callback'][0]),
				$this->applyMiddleware($route['middleware']) ?? $this->routeParams
			)
		) ?? $this->executeFallback();
	}

	/**
	 * Resolves the HTTP method, considering overrides specified via
	 * the `_method` parameter in a POST or GET request. This allows
	 * the simulation of methods like PUT, PATCH, and DELETE in clients
	 * that do not support them.
	 *
	 * @param string $method The original HTTP method.
	 * @return string The resolved HTTP method in uppercase.
	 */
	private function getHttpMethod(string $method): string
	{
		return match (true) {
			$this->isString($_POST['_method'] ?? $_GET['_method'] ?? null) &&
			$this->contains('PUT|PATCH|DELETE', $this->toUpper($_POST['_method'] ?? $_GET['_method'] ?? null))
				=> $this->toUpper($_POST['_method'] ?? $_GET['_method']),
			default => $this->toUpper($method),
		};
	}

	/**
	 * Executes the fallback route if no matching route is found.
	 * This method ensures that a default behavior is provided when the requested URI
	 * does not map to any defined route.
	 *
	 * @return mixed The result of the fallback route execution.
	 * @throws RouteNotFoundException If no fallback route is defined.
	 */
	private function executeFallback(): mixed
	{
		return match (true) {
			$this->fallbackRoute !== null => $this->reflector->invokeMethodWithArgs(
				$this->reflector->getMethodInfo($this->fallbackRoute, $this->fallbackRoute[1]),
				$this->reflector->instantiateClass($this->fallbackRoute[0]),
				[]
			),
			default => throw new RouteNotFoundException("No matching route and no fallback defined."),
		};
	}

	/**
	 * Processes and returns the middleware stack for a given route.
	 * Each middleware is instantiated and its specified method is invoked.
	 *
	 * @param array $middleware The middleware configurations to process.
	 */
	private function applyMiddleware(array $middleware): void
	{
		$this->map(
			fn($middlewareConfig) => $this->reflector->invokeMethod(
				$this->reflector->instantiateClass(
					$this->validateMiddleware($middlewareConfig[0], $middlewareConfig[1] ?? 'handle')
				),
				$middlewareConfig[1] ?? 'handle'
			),
			$middleware
		);
	}

	/**
	 * Validates that a provided class and method exist within the given context.
	 * Used for ensuring the validity of controllers and middleware configurations.
	 *
	 * @param string $class The fully qualified class name.
	 * @param string $method The method name to validate.
	 * @param string $context A description of the context (e.g., "Controller", "Middleware").
	 * @throws MiddlewareException If the class or method does not exist.
	 */
	private function checkClassMethod(string $class, string $method, string $context): void
	{
		$this->generalValidator->verify(
			[
				'class' => ['string', ['notEmpty']],
				'method' => ['string', ['notEmpty']]
			],
			['class' => $class, 'method' => $method]
		);

		match (true) {
			!$this->classExists($class) =>
				throw new MiddlewareException("{$context} class '{$class}' does not exist."),
			!$this->methodExists($class, $method) =>
				throw new MiddlewareException("Method '{$method}' does not exist in {$context} class '{$class}'."),
			default => null,
		};
	}

	/**
	 * Validates that the given controller and action exist.
	 *
	 * @param string $controller The fully qualified name of the controller class.
	 * @param string $action The name of the action method within the controller.
	 * @throws MiddlewareException If the controller or action does not exist.
	 */
	private function validateController(string $controller, string $action): void
	{
		$this->checkClassMethod($controller, $action, 'Controller');
	}

	/**
	 * Validates that the given middleware class and method exist.
	 *
	 * @param string $middlewareClass The fully qualified name of the middleware class.
	 * @param string $method The name of the method within the middleware class.
	 * @throws MiddlewareException If the middleware class or method does not exist.
	 */
	private function validateMiddleware(string $middlewareClass, string $method): void
	{
		$this->checkClassMethod($middlewareClass, $method, 'Middleware');
	}

	/**
	 * Validates and sanitizes a given path for use in route registration.
	 * Applies URL decoding and slug sanitization to ensure clean paths.
	 *
	 * @param string $path The path to sanitize.
	 * @return string The sanitized path.
	 */
	private function sanitizePath(string $path): string
	{
		return $this->patternSanitizer->clean(
			['path' => ['slug']],
			$this->generalSanitizer->clean(
				['path' => ['url', 'string']],
				['path' => $this->dataHandler->urlDecode($path)]
			)
		)['path'];
	}

	/**
	 * Validates that the given URI adheres to expected standards,
	 * ensuring it includes a path and meets URL format requirements.
	 *
	 * @param string $uri The URI to validate.
	 * @return string The validated URI.
	 * @throws MiddlewareException If the URI does not meet validation criteria.
	 */
	private function validateUri(string $uri): string
	{
		return $this->patternValidator->verify(
			['uri' => ['url', ['pathRequired']]],
			['uri' => $uri]
		)['uri'];
	}

	/**
	 * Validates the parameters extracted from a route.
	 *
	 * @param array $params The route parameters to validate.
	 * @return array The validated parameters.
	 */
	private function validateParams(array $params): array
	{
		return $this->map(
			fn($key, $value) => $this->patternValidator->verify(
				['value' => ['slug', ['notEmpty']]],
				['value' => $value]
			)['value'],
			$params
		);
	}

	/**
	 * Matches the provided URI and HTTP method to a registered route.
	 * Validates the extracted parameters and ensures the route exists.
	 *
	 * @param string $uri The request URI.
	 * @param string $method The HTTP method (e.g., GET, POST).
	 * @return array The matched route configuration, including parameters.
	 * @throws RouteNotFoundException If no matching route is found.
	 */
	private function matchUriToRoute(string $uri, string $method): array
	{
		return $this->mapFirst(
			fn($pattern, $route) => preg_match($this->convertPatternToRegex($pattern), $uri, $matches)
				? $this->merge($route, [
					'params' => $this->routeParams = $this->validateParams(
						$this->filter($matches, fn($key) => $this->isString($key), ARRAY_FILTER_USE_KEY)
					)
				])
				: null,
			$this->routes[$method] ?? []
		) ?? throw new RouteNotFoundException("Route not found for URI: $uri");
	}

	/**
	 * Converts a route pattern with placeholders into a regular expression.
	 * Supports custom constraints on parameters and optional parameters.
	 *
	 * @param string $pattern The route pattern to convert.
	 * @return string The generated regular expression for route matching.
	 */
	private function convertPatternToRegex(string $pattern): string
	{
		return '~^' . $this->replace(
			'/\{(\w+)(?::([^}]+))?\}/',
			fn($matches) => $this->isSet($matches[2])
				? "(?P<{$matches[1]}>{$matches[2]})"
				: "(?P<{$matches[1]}>[^/]+)",
			$pattern
		) . '$~';
	}

	/**
	 * Dynamically resolves the path to a specific file within a specified component directory.
	 * This method is crucial for modular architecture, ensuring that component files (e.g., controllers or middleware)
	 * are located and validated at runtime.
	 *
	 * @param string $component The name of the component directory (e.g., Controllers, Middlewares).
	 * @param string $fileName The name of the file to locate within the component directory.
	 * @return string The resolved path to the file.
	 * @throws MiddlewareException If the component directory or the file is not found.
	 */
	private function getComponentPath(string $component, string $fileName): string
	{
		return $this->fileFinder->find(['name' => $fileName],
			$this->directoryFinder->find(['name' => $component], $this->modulesPath)[0]
				?? throw new MiddlewareException("$component directory not found in module.")
		)[0] ?? throw new MiddlewareException("File '$fileName' not found in $component directory.");
	}

	/**
	 * Resolves the path to a controller file within the Controllers component directory.
	 * Ensures that controller classes can be dynamically located and validated during runtime.
	 *
	 * @param string $controller The name of the controller file to locate.
	 * @return string The resolved path to the controller file.
	 * @throws MiddlewareException If the Controllers directory or the specified controller file is not found.
	 */
	private function getControllerPath(string $controller): string
	{
		return $this->getComponentPath('Controllers', $controller);
	}

	/**
	 * Resolves the path to a middleware file within the Middlewares component directory.
	 * Ensures that middleware classes can be dynamically located and validated during runtime.
	 *
	 * @param string $middleware The name of the middleware file to locate.
	 * @return string The resolved path to the middleware file.
	 * @throws MiddlewareException If the Middlewares directory or the specified middleware file is not found.
	 */
	private function getMiddlewarePath(string $middleware): string
	{
		return $this->getComponentPath('Middlewares', $middleware);
	}

	/**
	 * Executes a given callback within a try-catch block.
	 * Converts any thrown exception into a MiddlewareException to standardize error handling
	 * during middleware and route processing.
	 *
	 * @param callable $callback The callback to execute.
	 * @return mixed The result of the callback execution.
	 * @throws MiddlewareException If the callback throws an exception.
	 */
	private function wrapInTry(callable $callback): mixed
	{
		try {
			return $callback();
		} catch (Throwable $e) {
			throw new MiddlewareException($e->getMessage(), 0, $e);
		}
	}
}
