<?php

namespace App\Utilities\Managers\Data;

use App\Exceptions\Http\MiddlewareException;
use App\Providers\ModuleProvider;
use App\Utilities\Finders\{
	DirectoryFinder,
	FileFinder
};
use App\Utilities\Handlers\NamespaceResolveHandler;
use App\Utilities\Traits\{
	ArrayTrait,
	TypeCheckerTrait,
	ErrorTrait
};

/**
 * Class ModuleManager
 *
 * Discovers modules, resolves declared classes, and exposes module resources
 * using the module folder structure as the authoritative source.
 */
class ModuleManager
{
	use ArrayTrait, TypeCheckerTrait, ErrorTrait;

	private string $base;

	/**
	 * Map of module name => absolute path.
	 *
	 * @var array<string, string>
	 */
	private array $modules = [];

	public function __construct(
		private DirectoryFinder $dirs,
		private FileFinder $files,
		private NamespaceResolveHandler $resolver,
		private ModuleProvider $provider
	) {
		$this->wrapInTry(fn() => $this->initializeModules(), MiddlewareException::class);
	}

	/**
	 * Returns all discovered module paths keyed by module name.
	 *
	 * @return array<string, string>
	 */
	public function getModules(): array
	{
		return $this->modules;
	}

	/**
	 * Returns a module path by module name.
	 *
	 * @param string $name
	 * @return string
	 */
	public function getModule(string $name): string
	{
		return $this->modules[$name]
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
	 */
	public function getFiles(string $module, string $subDir, array $filter = ['extension' => 'php'], array $sort = []): array
	{
		$modulePath = $this->resolveModulePath($module);
		$targetPath = $this->resolveSubdirectoryPath($modulePath, $subDir);

		if (!is_string($targetPath)) {
			throw new MiddlewareException("Subdirectory '{$subDir}' not found in module '{$module}'.");
		}

		return array_keys(
			$this->files->find(
				array_replace(['extension' => 'php'], $filter),
				$targetPath,
				$sort
			)
		);
	}

	/**
	 * Retrieves declared classes from a specific subdirectory within a module.
	 *
	 * @param string $module
	 * @param string $subDir
	 * @param array $filter
	 * @param array $sort
	 * @return array
	 */
	public function getClasses(string $module, string $subDir, array $filter = ['extension' => 'php'], array $sort = []): array
	{
		return $this->resolver->resolvePaths(
			$this->getFiles($module, $subDir, $filter, $sort)
		);
	}

	/**
	 * Collects declared classes from a subdirectory across all modules.
	 *
	 * @param string $subDir
	 * @param array $filter
	 * @param array $sort
	 * @return array
	 */
	public function collectClasses(string $subDir, array $filter = ['extension' => 'php'], array $sort = []): array
	{
		$classes = [];

		foreach ($this->modules as $modulePath) {
			$paths = $this->getModuleFilesByPath($modulePath, $subDir, $filter, $sort);

			if ($paths !== []) {
				$classes = array_merge($classes, $this->resolver->resolvePaths($paths));
			}
		}

		return $classes;
	}

	/**
	 * Collects files from a subdirectory across all modules.
	 *
	 * @param string $subDir
	 * @param array $filter
	 * @param array $sort
	 * @return array
	 */
	public function collectFiles(string $subDir, array $filter = ['extension' => 'php'], array $sort = []): array
	{
		$files = [];

		foreach ($this->modules as $modulePath) {
			$files = array_merge(
				$files,
				$this->getModuleFilesByPath($modulePath, $subDir, $filter, $sort)
			);
		}

		return $files;
	}

	/**
	 * Resolves a module class instance by alias or FQCN.
	 *
	 * @param string $alias
	 * @return object
	 */
	public function resolveModule(string $alias): object
	{
		return $this->wrapInTry(
			fn() => $this->provider->getModule($alias),
			MiddlewareException::class
		);
	}

	/**
	 * Locates the module root folder and registers discovered classes.
	 *
	 * @return void
	 */
	private function initializeModules(): void
	{
		$baseDirectories = $this->dirs->find(['name' => 'Modules', 'readable' => true]);
		$this->base = array_key_first($baseDirectories)
			?? throw new MiddlewareException('Modules directory not found or not readable.');

		$moduleDirectories = glob($this->base . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];

		foreach ($moduleDirectories as $modulePath) {
			$moduleName = basename($modulePath);

			if (is_readable($modulePath)) {
				$this->modules[$moduleName] = $modulePath;
			}
		}

		if ($this->modules === []) {
			throw new MiddlewareException('No readable modules found in the Modules directory.');
		}

		$this->populateProvider();
	}

	/**
	 * Populates the module provider with all declared classes.
	 *
	 * @return void
	 */
	private function populateProvider(): void
	{
		$this->wrapInTry(function (): void {
			$this->provider->populate($this->collectClasses(''));
			$this->provider->registerServices();
		}, MiddlewareException::class);
	}

	/**
	 * Resolves a module name or absolute path to a module path.
	 *
	 * @param string $module
	 * @return string
	 */
	private function resolveModulePath(string $module): string
	{
		if (isset($this->modules[$module])) {
			return $this->modules[$module];
		}

		if (in_array($module, $this->modules, true)) {
			return $module;
		}

		throw new MiddlewareException("Module '{$module}' not found or not readable.");
	}

	/**
	 * Resolves a case-insensitive subdirectory path inside a module.
	 *
	 * @param string $modulePath
	 * @param string $subDir
	 * @return string|null
	 */
	private function resolveSubdirectoryPath(string $modulePath, string $subDir): ?string
	{
		if ($subDir === '') {
			return $modulePath;
		}

		$directories = glob($modulePath . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];

		foreach ($directories as $directory) {
			if (strcasecmp(basename($directory), $subDir) === 0) {
				return $directory;
			}
		}

		return null;
	}

	/**
	 * Retrieves files from a module path and optional subdirectory path.
	 *
	 * @param string $modulePath
	 * @param string $subDir
	 * @param array $filter
	 * @param array $sort
	 * @return array
	 */
	private function getModuleFilesByPath(string $modulePath, string $subDir, array $filter, array $sort): array
	{
		$targetPath = $this->resolveSubdirectoryPath($modulePath, $subDir);

		if (!is_string($targetPath)) {
			return [];
		}

		return array_keys(
			$this->files->find(
				array_replace(['extension' => 'php'], $filter),
				$targetPath,
				$sort
			)
		);
	}
}
