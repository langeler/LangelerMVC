<?php

declare(strict_types=1);

namespace App\Utilities\Managers\Data;

use App\Exceptions\AppException;
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
		$this->wrapInTry(fn() => $this->initializeModules(), AppException::class);
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
			?? throw new AppException("Module '{$name}' not found or not readable.");
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

		if (!$this->isString($targetPath)) {
			throw new AppException("Subdirectory '{$subDir}' not found in module '{$module}'.");
		}

		return $this->getKeys(
			$this->files->find(
				$this->replace(['extension' => 'php'], $filter),
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
				$classes = $this->merge($classes, $this->resolver->resolvePaths($paths));
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
			$files = $this->merge(
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
			AppException::class
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
		$this->base = $this->keyFirst($baseDirectories)
			?? throw new AppException('Modules directory not found or not readable.');

		$moduleDirectories = glob($this->base . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) ?: [];

		foreach ($moduleDirectories as $modulePath) {
			$moduleName = basename($modulePath);

			if ($this->isReadable($modulePath)) {
				$this->modules[$moduleName] = $modulePath;
			}
		}

		if ($this->modules === []) {
			throw new AppException('No readable modules found in the Modules directory.');
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
		}, AppException::class);
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

		if ($this->isInArray($module, $this->modules, true)) {
			return $module;
		}

		throw new AppException("Module '{$module}' not found or not readable.");
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

		if (!$this->isString($targetPath)) {
			return [];
		}

		return $this->getKeys(
			$this->files->find(
				$this->replace(['extension' => 'php'], $filter),
				$targetPath,
				$sort
			)
		);
	}
}
