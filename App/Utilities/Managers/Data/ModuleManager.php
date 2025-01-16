<?php

namespace App\Core;

use App\Exceptions\Http\MiddlewareException;
use App\Providers\ModuleProvider;
use App\Utilities\Finders\DirectoryFinder;
use App\Utilities\Finders\FileFinder;
use App\Utilities\Traits\ArrayTrait;
use App\Utilities\Traits\TypeCheckerTrait;
use App\Utilities\Traits\ErrorTrait;

/**
 * Class ModuleManager
 *
 * Manages the retrieval, organization, and resolution of modules and their classes.
 */
class ModuleManager
{
	use ArrayTrait, TypeCheckerTrait, ErrorTrait;

	private string $base;
	private array $modules;

	public function __construct(
		private DirectoryFinder $dirs,
		private FileFinder $files,
		private NamespaceResolveHandler $resolver,
		private ModuleProvider $provider
	) {
		$this->wrapInTry(fn() => $this->initializeModules(), MiddlewareException::class);
	}

	/**
	 * Initializes the Modules directory, retrieves module paths,
	 * and populates the provider with classes from all modules.
	 *
	 * @throws MiddlewareException
	 */
	private function initializeModules(): void
	{
		$this->base = $this->dirs->find(['name' => 'Modules', 'readable' => true])[0]
			?? throw new MiddlewareException('Modules directory not found or not readable.');

		$this->modules = $this->filterNonEmpty(
			$this->dirs->find(['readable' => true], $this->base)
		);

		$this->isEmpty($this->modules) &&
			throw new MiddlewareException('No readable modules found in the Modules directory.');

		$this->populateProvider();
	}

	/**
	 * Retrieves all module paths.
	 *
	 * @return array
	 */
	public function getModules(): array
	{
		return $this->modules;
	}

	/**
	 * Retrieves a specific module path by its name.
	 *
	 * @param string $name
	 * @return string
	 * @throws MiddlewareException
	 */
	public function getModule(string $name): string
	{
		return $this->dirs->find(['name' => $name, 'readable' => true], $this->base)[0]
			?? throw new MiddlewareException("Module '{$name}' not found or not readable.");
	}

	/**
	 * Retrieves files from a specific subdirectory within a module.
	 *
	 * @param string $module
	 * @param string $subDir
	 * @param array $filter
	 * @param array $sort
	 * @return array
	 * @throws MiddlewareException
	 */
	public function getFiles(string $module, string $subDir, array $filter = ['extension' => 'php'], array $sort = []): array
	{
		return $this->files->find(
			$this->mergeRecursive(['extension' => 'php'], $filter),
			$this->dirs->find(['name' => $subDir, 'readable' => true], $this->getModule($module))[0]
				?? throw new MiddlewareException("Subdirectory '{$subDir}' not found in module '{$module}'."),
			$sort
		);
	}

	/**
	 * Retrieves all classes from a specific subdirectory within a module.
	 *
	 * @param string $module
	 * @param string $subDir
	 * @param array $filter
	 * @param array $sort
	 * @return array
	 * @throws MiddlewareException
	 */
	public function getClasses(string $module, string $subDir, array $filter = ['extension' => 'php'], array $sort = []): array
	{
		return $this->resolver->resolvePaths(
			$this->getFiles($module, $subDir, $filter, $sort)
		);
	}

	/**
	 * Collects all classes from a subdirectory across all modules.
	 *
	 * @param string $subDir
	 * @param array $filter
	 * @param array $sort
	 * @return array
	 */
	public function collectClasses(string $subDir, array $filter = ['extension' => 'php'], array $sort = []): array
	{
		return $this->reduce(
			$this->modules,
			fn($acc, $module) => $this->merge(
				$acc,
				$this->resolver->resolvePaths(
					$this->files->find(
						$this->mergeRecursive(['extension' => 'php'], $filter),
						$this->dirs->find(['name' => $subDir, 'readable' => true], $module)[0] ?? null,
						$sort
					)
				)
			),
			[]
		);
	}

	/**
	 * Populates the ModuleProvider with classes from all modules and registers them.
	 *
	 * @return void
	 * @throws MiddlewareException
	 */
	private function populateProvider(): void
	{
		$this->wrapInTry(
			fn() => [
				$this->provider->populate($this->collectClasses('')),
				$this->provider->registerServices()
			],
			MiddlewareException::class
		);
	}

/**
 * Retrieves a module class instance by alias from the ModuleProvider.
 *
 * This method leverages the ModuleProvider's `getModule` method to resolve
 * and return an instance of the requested module class.
 *
 * @param string $alias The alias of the module class.
 * @return object The resolved module class instance.
 * @throws MiddlewareException If the module cannot be resolved.
 */
public function resolveModule(string $alias): object
{
	return $this->wrapInTry(
		fn() => $this->provider->getModule($alias),
		MiddlewareException::class
	);
}

	/**
	 * Extracts the module name from its path.
	 *
	 * @param string $path
	 * @return string
	 */
	private function extractModuleName(string $path): string
	{
		return $this->keyLast(explode(DIRECTORY_SEPARATOR, $path));
	}
}
