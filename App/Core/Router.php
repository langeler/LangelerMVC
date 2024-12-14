<?php

namespace App\Core;

use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Finders\FileFinder;
use App\Utilities\Handlers\DataStructureHandler;
use App\Utilities\Managers\CacheManager;
use App\Utilities\Managers\ReflectionManager;
use App\Utilities\Sanitation\NetworkSanitizer;
use App\Utilities\Validation\GeneralValidator;
use App\Helpers\ExistenceChecker;
use App\Helpers\TypeChecker;
use App\Helpers\ArrayHelper;
use App\Exceptions\RouteNotFoundException;
use App\Exceptions\Http\MiddlewareException;
use App\Utilities\Traits\ManipulationTrait;
use Throwable;

class Router
{
	use ManipulationTrait;

	private array $routes = [];
	private array $middlewareGroups = [];
	private $notFoundHandler = null;
	protected ?string $groupPrefix = null;
	protected array $groupMiddleware = [];
	protected ?string $basePath = null;
	protected int $cacheDuration = 600;

	public function __construct(
		protected DirectoryFinder $directoryFinder,
		protected FileFinder $fileFinder,
		protected ExistenceChecker $existenceChecker,
		protected TypeChecker $typeChecker,
		protected ArrayHelper $arrayHelper,
		protected DataStructureHandler $dataHandler,
		protected ReflectionManager $reflector,
		protected NetworkSanitizer $urlSanitizer,
		protected GeneralValidator $validator,
		protected CacheManager $cache
	) {
		$this->loadRoutes();
	}

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

	public function addRoute(string $method, string $path, string $module, string $controller, string $action, array $middleware = []): void
	{
		try {
			$this->routes[$method][$this->sanitizePath($this->groupPrefix . $path ?? $path)] = [
				'callback' => ["App\\Modules\\$module\\Controllers\\$controller", $action],
				'middleware' => $this->mergeMiddleware($middleware),
			];
			$this->validateController("App\\Modules\\$module\\Controllers\\$controller", $action);
		} catch (Throwable $e) {
			throw new MiddlewareException("Error registering route: " . $e->getMessage(), 0, $e);
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

	public function patch(string $path, string $module, string $controller, string $action, array $middleware = []): void
	{
		$this->addRoute('PATCH', $path, $module, $controller, $action, $middleware);
	}

	public function options(string $path, string $module, string $controller, string $action, array $middleware = []): void
	{
		$this->addRoute('OPTIONS', $path, $module, $controller, $action, $middleware);
	}

	public function loadRoutes(): void
	{
		try {
			$this->routes = $this->cache->get('routes')
				? json_decode($this->cache->get('routes'), true)
				: $this->cacheAndReturnRoutes();
		} catch (Throwable $e) {
			throw new RouteNotFoundException("Error loading routes: " . $e->getMessage(), 0, $e);
		}
	}

	private function cacheAndReturnRoutes(): array
	{
		$this->cache->set('routes', json_encode($this->buildModuleRoutes()), $this->cacheDuration);
		return $this->buildModuleRoutes();
	}

	private function buildModuleRoutes(): array
	{
		try {
			return array_reduce(
				array_merge(
					...array_map(
						fn($module) => $this->directoryFinder->find(['name' => 'Routes'], $module),
						$this->directoryFinder->find(['name' => 'Modules'])
					)
				),
				fn($acc, $routePath) => $acc + $this->processRouteFilesForModule($routePath),
				[]
			);
		} catch (Throwable $e) {
			throw new RouteNotFoundException("Error building module routes: " . $e->getMessage(), 0, $e);
		}
	}

	private function processRouteFilesForModule(string $routePath): array
	{
		try {
			return array_map(
				fn($routeFile) => require $routeFile,
				$this->fileFinder->find(['extension' => 'php'], $routePath)
			);
		} catch (Throwable $e) {
			throw new RouteNotFoundException("Error processing route files in module: " . $e->getMessage(), 0, $e);
		}
	}

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
			return $this->notFoundHandler ? call_user_func($this->notFoundHandler) : throw new MiddlewareException("Error dispatching route: " . $e->getMessage(), 0, $e);
		} catch (Throwable $e) {
			throw new MiddlewareException("Error dispatching route: " . $e->getMessage(), 0, $e);
		}
	}

	private function resolveRoute(string $uri, string $method): array
	{
		return array_reduce(
			$this->routes[$method] ?? [],
			fn($carry, $routeConfig) => $carry ?: (
				preg_match('#^' . preg_replace('/\{[^\}]+\}/', '([^/]+)', $routeConfig['route']) . '$#', $this->sanitizePath($uri), $matches)
					? $this->setRouteParams(array_slice($matches, 1)) + $routeConfig['callback']
					: null
			),
			null
		) ?? throw new RouteNotFoundException("Route not found for URI: " . $this->sanitizePath($uri));
	}

	private function setRouteParams(array $matches): void
	{
		$this->dataHandler->setData('routeParams', $matches);
	}

	private function sanitizePath(string $path): string
	{
		return $this->urlSanitizer->sanitizeUrlHttpHttps('/' . trim($path, '/'));
	}

	private function extractParams(string $uri): array
	{
		return $this->dataHandler->getData('routeParams') ?? [];
	}

	private function validateController(string $controllerClass, string $method): void
	{
		if (!$this->existenceChecker->classExists($controllerClass) || !$this->existenceChecker->methodExists($controllerClass, $method)) {
			throw new RouteNotFoundException("Controller or method not found: $controllerClass@$method");
		}
	}

	private function mergeMiddleware(array $middleware): array
	{
		return $this->arrayHelper->merge($this->groupMiddleware, $middleware);
	}
}
