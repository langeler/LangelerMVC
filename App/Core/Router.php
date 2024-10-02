<?php

namespace App\Core;

use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Finders\FileFinder;
use App\Utilities\Handlers\DataStructureHandler;
use App\Utilities\Managers\ReflectionManager;
use App\Utilities\Sanitation\NetworkSanitizer;
use App\Utilities\Validation\GeneralValidator;
use App\Helpers\ExistenceChecker;
use App\Helpers\TypeChecker;
use App\Helpers\ArrayHelper;
use App\Exceptions\RouteNotFoundException;
use App\Exceptions\Http\MiddlewareException;
use App\Core\Cache;
use App\Utilities\Traits\ManipulationTrait;

/**
 * Class Router
 *
 * This class is responsible for managing route registration, handling middleware,
 * dispatching routes, and caching route information. It simplifies the process of
 * defining HTTP routes, applying middleware, and invoking controllers based on the request.
 *
 * @package App\Core
 */
class Router
{
	use ManipulationTrait;

	private array $routes = [];
	private array $middlewareGroups = [];

	/**
	 * @var callable|null
	 */
	private $notFoundHandler = null; // Remove ?callable for compatibility

	protected ?string $groupPrefix = null;
	protected array $groupMiddleware = [];

	protected DirectoryFinder $directoryFinder;
	protected FileFinder $fileFinder;
	protected Cache $cache;

	protected ?string $basePath = null;
	protected int $cacheDuration = 600;

	private ExistenceChecker $existenceChecker;
	private TypeChecker $typeChecker;
	private ArrayHelper $arrayHelper;
	private DataStructureHandler $dataHandler;
	private ReflectionManager $reflector;
	private NetworkSanitizer $urlSanitizer;
	private GeneralValidator $validator;

	/**
	 * Router constructor.
	 * Initializes required dependencies and loads routes.
	 *
	 * @param DirectoryFinder $directoryFinder
	 * @param FileFinder $fileFinder
	 * @param ExistenceChecker $existenceChecker
	 * @param TypeChecker $typeChecker
	 * @param ArrayHelper $arrayHelper
	 * @param DataStructureHandler $dataHandler
	 * @param ReflectionManager $reflector
	 * @param NetworkSanitizer $urlSanitizer
	 * @param GeneralValidator $validator
	 * @param Cache $cache
	 */
	public function __construct(
		DirectoryFinder $directoryFinder,
		FileFinder $fileFinder,
		ExistenceChecker $existenceChecker,
		TypeChecker $typeChecker,
		ArrayHelper $arrayHelper,
		DataStructureHandler $dataHandler,
		ReflectionManager $reflector,
		NetworkSanitizer $urlSanitizer,
		GeneralValidator $validator,
		Cache $cache
	) {
		$this->directoryFinder = $directoryFinder;
		$this->fileFinder = $fileFinder;
		$this->existenceChecker = $existenceChecker;
		$this->typeChecker = $typeChecker;
		$this->arrayHelper = $arrayHelper;
		$this->dataHandler = $dataHandler;
		$this->reflector = $reflector;
		$this->urlSanitizer = $urlSanitizer;
		$this->validator = $validator;
		$this->cache = $cache;

		$this->loadRoutes();
	}

	// === Route Group Management ===
	public function group(string $prefix, array $middleware = []): void
	{
		$this->groupPrefix = $this->sanitizePath($prefix);
		$this->groupMiddleware = $middleware;
	}

	public function endGroup(): void
	{
		$this->groupPrefix = null;
		$this->groupMiddleware = [];
	}

	// === Route Registration ===
	public function addRoute(string $method, string $path, string $module, string $controller, string $action, array $middleware = []): void
	{
		try {
			$fullPath = $this->groupPrefix ? $this->groupPrefix . $path : $path;
			$this->routes[$method][$this->sanitizePath($fullPath)] = [
				'callback' => ["App\\Modules\\$module\\Controllers\\$controller", $action],
				'middleware' => $this->mergeMiddleware($middleware),
			];
			$this->validateController("App\\Modules\\$module\\Controllers\\$controller", $action);
		} catch (\InvalidArgumentException $e) {
			throw new RouteNotFoundException("Controller or method not found: App\\Modules\\$module\\Controllers\\$controller@$action. Error: " . $e->getMessage());
		} catch (\Exception $e) {
			throw new MiddlewareException("Error registering route: " . $e->getMessage());
		}
	}

	public function get(string $path, string $module, string $controller, string $action, array $middleware = []): void
	{
		$this->addRoute('GET', $path, $module, $controller, $action, $middleware);
	}

	public function post(string $path, string $module, string $controller, string $action, array $middleware = []): void
	{
		$this->addRoute('POST', $path, $module, $controller, $action, $middleware);
	}

	public function put(string $path, string $module, string $controller, string $action, array $middleware = []): void
	{
		$this->addRoute('PUT', $path, $module, $controller, $action, $middleware);
	}

	public function delete(string $path, string $module, string $controller, string $action, array $middleware = []): void
	{
		$this->addRoute('DELETE', $path, $module, $controller, $action, $middleware);
	}

	// === Route Loading & Caching ===
	public function loadRoutes(): void
	{
		// Attempt to load routes from cache
		if ($cachedRoutes = $this->cache->get('routes')) {
			$this->routes = $cachedRoutes;
		} else {
			// Routes not cached, so build and cache them
			$this->routes = $this->buildModuleRoutes();
			$this->cache->set('routes', $this->routes, $this->cacheDuration);
		}

		// Check if any routes exist, else throw an error or handle gracefully
		if (empty($this->routes)) {
			throw new RouteNotFoundException("No routes found.");
		}
	}

	/**
	 * Builds routes for all modules by scanning the directory.
	 *
	 * @return array Generated routes for all modules.
	 */
	private function buildModuleRoutes(): array
	{
		$routes = [];

		// Find all module directories using DirectoryFinder with the correct criteria
		$modules = $this->directoryFinder->find(['name' => 'Modules', 'depth' => 2]);

		if (empty($modules)) {
			var_dump($modules);
			throw new RouteNotFoundException("No modules found.");
		}

		foreach ($modules as $module) {
			$routes = $this->processRouteFilesForModule($module, $routes);
		}

		return $routes;
	}

	/**
	 * Processes the route files for a specific module.
	 *
	 * @param $module Module directory information.
	 * @param array $routes Accumulated routes.
	 * @return array Updated routes with module-specific routes added.
	 */
private function processRouteFilesForModule($module, array $routes): array
	 {
		 $routePath = $module->getPathname() . '/Routes';

		 // Check if the path is a valid directory
		 if ($this->typeChecker->isDirectory($routePath)) {
			 // Get all directory contents
			 $routeFiles = $this->fileFinder->getDirectoryContents($routePath);

			 // Filter only PHP files
			 foreach ($routeFiles as $routeFile) {
				 if (pathinfo($routeFile, PATHINFO_EXTENSION) === 'php') {
					 // Full path of the route file
					 $filePath = $routePath . DIRECTORY_SEPARATOR . $routeFile;

					 // Require the route file and add its path to the routes array
					 require $filePath;
					 $routes[$module->getFilename()][] = $filePath;
				 }
			 }
		 }

		 return $routes;
	 }
	// === Route Dispatching ===
	public function dispatch(string $uri, string $method)
	{
		try {
			[$controller, $action] = $this->resolveRoute($uri, $method);
			return $this->reflector->invokeMethodWithArgs(
				$this->reflector->getMethodInfo($controller, $action),
				$this->reflector->instantiateClass($controller),
				$this->extractParams($uri)
			);
		} catch (RouteNotFoundException $e) {
			if ($this->notFoundHandler) {
				return call_user_func($this->notFoundHandler);
			}
			throw new MiddlewareException("Error dispatching route: " . $e->getMessage());
		} catch (\Exception $e) {
			throw new MiddlewareException("Error dispatching route: " . $e->getMessage());
		}
	}

	private function resolveRoute(string $uri, string $method): array
	{
		return $this->routes[$method][$this->sanitizePath($uri)]
			?? throw new RouteNotFoundException("Route not found for URI: " . $this->sanitizePath($uri));
	}

	// === Middleware Management ===
	private function applyMiddleware(array $middlewares, string $route): void
	{
		try {
			foreach ($middlewares as $middleware) {
				$this->validateMiddleware($middleware);
				$this->dataHandler->pushToStack($this->middlewareStack, $middleware);
			}

			while (!$this->middlewareStack->isEmpty()) {
				$this->reflector->invokeFunction($this->dataHandler->popFromStack($this->middlewareStack));
			}
		} catch (\Exception $e) {
			throw new MiddlewareException("Error applying middleware for route: $route - " . $e->getMessage());
		}
	}

	// === Sanitization & Validation ===
	private function sanitizePath(string $path): string
	{
		return $this->urlSanitizer->sanitizeUrlHttpHttps('/' . $this->trim($path, '/'));
	}

	private function extractParams(string $uri): array
	{
		return $this->arrayHelper->getValues($this->split('/', $this->trim($uri, '/')));
	}

	private function validateMiddleware(callable $middleware): void
	{
		try {
			$this->validator->validateBoolean($this->typeChecker->isCallable($middleware))
				?: throw new MiddlewareException("Invalid middleware: " . gettype($middleware));
		} catch (\Exception $e) {
			throw new MiddlewareException("Middleware validation error: " . $e->getMessage());
		}
	}

	private function validateController(string $controllerClass, string $method): void
	{
		try {
			$this->existenceChecker->classExists($controllerClass)
				?: throw new RouteNotFoundException("Controller not found: $controllerClass");

			$this->existenceChecker->methodExists($controllerClass, $method)
				?: throw new RouteNotFoundException("Method not found: $controllerClass@$method");
		} catch (\Exception $e) {
			throw new RouteNotFoundException("Controller validation error: " . $e->getMessage());
		}
	}

	// === Helper Methods ===
	private function mergeMiddleware(array $middleware): array
	{
		return $this->arrayHelper->merge($this->groupMiddleware, $middleware);
	}
}
